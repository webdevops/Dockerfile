<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\FileReader;

#[CoversClass(FileReader::class)]
final class FileReaderTest extends TestCase
{
    private const string ROOT = TemporaryRepository::FIXTURE;

    public function testCollectsTheGeneratedDockerfilesSorted(): void
    {
        // docker/php/8.4 links to docker/php-official/8.4 and is left out, as in the repository
        self::assertSame([
            'docker/base-app/ubuntu-22.04/Dockerfile',
            'docker/base/alpine/Dockerfile',
            'docker/base/ubuntu-22.04/Dockerfile',
            'docker/php-nginx-dev/8.5-alpine/Dockerfile',
            'docker/php-nginx/8.5-alpine/Dockerfile',
            'docker/php-official/8.4/Dockerfile',
            'docker/php-official/8.5-alpine/Dockerfile',
            'docker/php-official/8.5/Dockerfile',
            'docker/toolbox/latest/Dockerfile',
        ], new FileReader(self::ROOT)->collectDockerfiles());
    }

    public function testImageWithAnOutsideBaseImageIsBuiltAfterTheToolbox(): void
    {
        $node = $this->info('base/ubuntu-22.04');

        self::assertSame('webdevops/base:ubuntu-22.04', $node['id']);
        self::assertSame('webdevops/base:ubuntu-22.04', $node['name']);
        self::assertSame('base', $node['image']);
        self::assertSame('ubuntu-22.04', $node['tag']);
        self::assertSame('docker/base/ubuntu-22.04/Dockerfile', $node['file']);
        self::assertSame('ubuntu:22.04', $node['baseImage']);
        self::assertSame('webdevops/toolbox:latest', $node['parent']);
        self::assertSame(0, $node['imageParent'], 'the toolbox is no image to build on');
        self::assertNull($node['imageParentRef']);
    }

    public function testAutoLatestTagIsPublishedAsLatestToo(): void
    {
        self::assertSame(['webdevops/base:latest'], $this->info('base/ubuntu-22.04')['aliases']);
        self::assertSame([], $this->info('base/alpine')['aliases']);
    }

    public function testServerspecSettingsComeFromTheTag(): void
    {
        self::assertSame([
            'DOCKER_IMAGE' => 'webdevops/base:ubuntu-22.04',
            'DOCKER_TAG' => 'ubuntu-22.04',
            'OS_FAMILY' => 'ubuntu',
            'OS_VERSION' => '22.04',
        ], $this->info('base/ubuntu-22.04')['serverspec']);
    }

    public function testFirstMatchingImageSettingsAreAdded(): void
    {
        $serverspec = $this->info('php-official/8.5-alpine')['serverspec'];

        self::assertSame('alpine', $serverspec['OS_FAMILY']);
        self::assertSame('3', $serverspec['OS_VERSION'], 'not 12 from the second matching settings');
        self::assertSame('1', $serverspec['PHP_OFFICIAL']);
    }

    public function testDefaultServerspecSettings(): void
    {
        $serverspec = $this->info('toolbox/latest')['serverspec'];

        self::assertSame('ubuntu', $serverspec['OS_FAMILY']);
        self::assertSame('22.04', $serverspec['OS_VERSION']);
    }

    public function testToolboxHasNoParent(): void
    {
        $node = $this->info('toolbox/latest');

        self::assertSame(0, $node['parent']);
        self::assertSame([], $node['imageDependencies']);
    }

    public function testOfficialImageIsPublishedAsPhp(): void
    {
        $node = $this->info('php-official/8.4');

        self::assertSame('webdevops/php:8.4', $node['id']);
        self::assertSame('webdevops/php:8.4', $node['name']);
        self::assertSame('php', $node['image']);
        self::assertSame(['webdevops/php-official:8.4'], $node['aliases']);
        self::assertSame('webdevops/php-official:8.4', $node['serverspec']['DOCKER_IMAGE']);
        self::assertSame('debian', $node['serverspec']['OS_FAMILY']);
    }

    public function testInternalParentImage(): void
    {
        $node = $this->info('php-nginx/8.5-alpine');

        self::assertSame('webdevops/php:8.5-alpine', $node['parent']);
        self::assertSame('webdevops/php:8.5-alpine', $node['imageParent']);
        self::assertSame('webdevops/php:8.5-alpine', $node['imageParentRef']);
        self::assertSame('webdevops/php:8.5-alpine', $node['baseImage']);
    }

    public function testLatestParentResolvesToTheAutoLatestTag(): void
    {
        $node = $this->info('base-app/ubuntu-22.04');

        self::assertSame('webdevops/base:ubuntu-22.04', $node['parent']);
        self::assertSame('webdevops/base:ubuntu-22.04', $node['imageParent']);
        self::assertSame('webdevops/base:latest', $node['imageParentRef'], 'the reference as written in the FROM line');
    }

    public function testCopyFromAnInternalImageIsADependency(): void
    {
        self::assertSame(
            ['webdevops/toolbox' => 'webdevops/toolbox:latest'],
            $this->info('base/ubuntu-22.04')['imageDependencies'],
            'an untagged reference means latest, and the toolbox has a latest directory',
        );
        self::assertSame(
            ['webdevops/toolbox:latest' => 'webdevops/toolbox:latest'],
            $this->info('base/alpine')['imageDependencies'],
        );
    }

    public function testAutoLatestTag(): void
    {
        self::assertSame('ubuntu-22.04', new FileReader(self::ROOT)->autoLatestTag());
    }

    public function testDockerfileWithoutHeaderIsAnError(): void
    {
        $repository = new TemporaryRepository();
        $repository->write('docker/broken/latest/Dockerfile', "FROM alpine:3.22\n");

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('docker/broken/latest/Dockerfile has no "# Dockerfile for webdevops/<image>:<tag>" header.');
            new FileReader($repository->path)->getInfo('docker/broken/latest/Dockerfile');
        } finally {
            $repository->remove();
        }
    }

    public function testMissingDockerfileIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read docker/missing/latest/Dockerfile.');
        new FileReader(self::ROOT)->getInfo('docker/missing/latest/Dockerfile');
    }

    /**
     * @return array<string, mixed>
     */
    private function info(string $directory): array
    {
        return new FileReader(self::ROOT)->getInfo('docker/' . $directory . '/Dockerfile');
    }
}
