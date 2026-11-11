<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Provision;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\Provision\Provisioner;
use Webdevops\Build\Tests\TemporaryRepository;

use function fileperms;

#[CoversClass(Provisioner::class)]
final class ProvisionerTest extends TestCase
{
    private TemporaryRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TemporaryRepository();
    }

    protected function tearDown(): void
    {
        $this->repository->remove();
    }

    public function testCopiesTheSourcesIntoTheConfDirectoryOfTheirTags(): void
    {
        $this->provision();

        self::assertSame("general\n", $this->repository->read('docker/base/ubuntu-22.04/conf/etc/os'));
        self::assertSame("php\n", $this->repository->read('docker/php-official/8.4/conf/etc/php.conf'));
        self::assertSame("php\n", $this->repository->read('docker/php-official/8.5/conf/etc/php.conf'), '8.5 does not match *-alpine');
        self::assertSame("#!/bin/sh\necho service\n", $this->repository->read('docker/php-official/8.5-alpine/conf/bin/service'));
    }

    public function testLaterSourcesReplaceFilesOfEarlierOnes(): void
    {
        $this->provision();

        self::assertSame("alpine\n", $this->repository->read('docker/base/alpine/conf/etc/os'));
        self::assertSame("php alpine\n", $this->repository->read('docker/php-official/8.5-alpine/conf/etc/php.conf'));
    }

    public function testKeepsThePermissionsOfTheFiles(): void
    {
        $this->provision();

        self::assertSame(0755, fileperms($this->repository->path . '/docker/base/alpine/conf/bin/service') & 0777);
        self::assertSame(0644, fileperms($this->repository->path . '/docker/base/alpine/conf/etc/os') & 0777);
    }

    public function testLeavesOutTheFilesOfTheFinder(): void
    {
        $this->repository->write('provisioning/base/general/etc/.DS_Store', '');

        $this->provision();

        self::assertFileDoesNotExist($this->repository->path . '/docker/base/alpine/conf/etc/.DS_Store');
        self::assertSame("alpine\n", $this->repository->read('docker/base/alpine/conf/etc/os'));
    }

    public function testEmptiesTheConfDirectoriesFirst(): void
    {
        $this->repository->write('docker/base/alpine/conf/etc/removed-from-provisioning', 'old');
        $this->repository->write('docker/php-official/8.4/conf/etc/os', 'old');

        $this->provision();

        self::assertFileDoesNotExist($this->repository->path . '/docker/base/alpine/conf/etc/removed-from-provisioning');
        self::assertSame("general\n", $this->repository->read('docker/php-official/8.4/conf/etc/os'));
    }

    public function testLeavesImagesAloneThatConfProvisionYmlDoesNotName(): void
    {
        $this->repository->write('docker/base-app/ubuntu-22.04/conf/etc/own', 'own');

        $this->provision();

        self::assertSame("not provisioned\n", $this->repository->read('docker/toolbox/latest/conf/hand-made'));
        self::assertSame('own', $this->repository->read('docker/base-app/ubuntu-22.04/conf/etc/own'));
    }

    public function testTagListAndPatterns(): void
    {
        $this->configure("provision:\n  base:\n    configuration:\n      base/alpine: ['ALPINE', 'ubuntu-*']\n");

        $this->provision();

        self::assertSame("alpine\n", $this->repository->read('docker/base/alpine/conf/etc/os'), 'tags match regardless of case');
        self::assertSame("alpine\n", $this->repository->read('docker/base/ubuntu-22.04/conf/etc/os'));
    }

    public function testTagMatchesTheWholeDirectoryName(): void
    {
        $this->repository->write('docker/base/alpine-edge/Dockerfile.twig', '');

        $this->provision();

        self::assertSame("general\n", $this->repository->read('docker/base/alpine-edge/conf/etc/os'), 'alpine does not match alpine-edge');
    }

    public function testNewTagIsProvisionedBeforeItsDockerfileIsGenerated(): void
    {
        $this->repository->write('docker/base/alpine-edge/Dockerfile.twig', '');

        $this->provision();

        self::assertSame("general\n", $this->repository->read('docker/base/alpine-edge/conf/etc/os'));
    }

    public function testDirectoryWithoutTemplateIsNoTag(): void
    {
        // generate:dockerfile reports such a directory
        $this->repository->write('docker/base/alpine-edge/conf/etc/os', 'old');

        $this->provision();

        self::assertSame('old', $this->repository->read('docker/base/alpine-edge/conf/etc/os'));
    }

    public function testChecksTheWholeConfigurationBeforeChangingFiles(): void
    {
        $this->repository->write('docker/base/alpine/conf/etc/os', 'old');
        $this->configure("provision:\n  base:\n    configuration:\n      base/general: '*'\n  php-official:\n    configuration:\n      php/general: '8.6'\n");

        try {
            $this->provision();
            self::fail('8.6 is no tag of php-official');
        } catch (RuntimeException $runtimeException) {
            self::assertSame('conf/provision.yml copies provisioning/php/general into webdevops/php-official:8.6, but webdevops/php-official has no such tag under docker/.', $runtimeException->getMessage());
            self::assertSame('old', $this->repository->read('docker/base/alpine/conf/etc/os'));
        }
    }

    public function testLogsEachSource(): void
    {
        self::assertSame([
            'base/general => webdevops/base:*',
            'base/alpine => webdevops/base:alpine',
            'base/general => webdevops/php-official:*',
            'php/general => webdevops/php-official:*',
            'php/official-alpine => webdevops/php-official:*-alpine',
        ], $this->provision());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'missing source' => [
            "provision:\n  base:\n    configuration:\n      base/missing: '*'\n",
            'conf/provision.yml copies provisioning/base/missing into webdevops/base, but it does not exist.',
        ];
        yield 'no provision section' => ["images: []\n", 'conf/provision.yml has no "provision" section.'];
        yield 'tag without a directory' => [
            "provision:\n  base:\n    configuration:\n      base/alpine: alpin\n",
            'conf/provision.yml copies provisioning/base/alpine into webdevops/base:alpin, but webdevops/base has no such tag under docker/.',
        ];
        yield 'image without tags' => [
            "provision:\n  missing-image:\n    configuration:\n      base/general: '*'\n",
            'conf/provision.yml copies provisioning/base/general into webdevops/missing-image:*, but webdevops/missing-image has no such tag under docker/.',
        ];
        yield 'image whose tags are symbolic links' => [
            // docker/php/8.4 links to docker/php-official/8.4
            "provision:\n  php:\n    configuration:\n      php/general: '*'\n",
            'conf/provision.yml copies provisioning/php/general into webdevops/php:*, but webdevops/php has no such tag under docker/.',
        ];
        yield 'empty tag list' => [
            "provision:\n  base:\n    configuration:\n      base/general: []\n",
            'conf/provision.yml has no tag for provisioning/base/general in webdevops/base.',
        ];
        $settings = 'conf/provision.yml: webdevops/base needs "configuration", which maps directories of provisioning/ to tags, and no other setting.';
        yield 'misspelt configuration' => ["provision:\n  base:\n    configuraton:\n      base/general: '*'\n", $settings];
        yield 'another setting' => ["provision:\n  base:\n    configuration:\n      base/general: '*'\n    tags: '*'\n", $settings];
        yield 'configuration that is no map' => ["provision:\n  base:\n    configuration: base/general\n", $settings];
        yield 'no settings' => ["provision:\n  base: ~\n", $settings];
        yield 'settings that are no map' => ["provision:\n  base: base/general\n", $settings];
        yield 'tag that is no text' => [
            "provision:\n  base:\n    configuration:\n      base/general: 8\n",
            'conf/provision.yml has an invalid tag for provisioning/base/general in webdevops/base.',
        ];
    }

    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfigurationIsAnError(string $configuration, string $message): void
    {
        $this->configure($configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->provision();
    }

    private function configure(string $configuration): void
    {
        $this->repository->write('conf/provision.yml', $configuration);
    }

    /**
     * @return list<string> the log lines
     */
    private function provision(): array
    {
        $lines = [];
        new Provisioner($this->repository->path)->provision(function (string $line) use (&$lines): void {
            $lines[] = $line;
        });
        return $lines;
    }
}
