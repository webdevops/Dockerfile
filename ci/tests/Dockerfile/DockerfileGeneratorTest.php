<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Dockerfile;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Twig\Error\RuntimeError;
use Webdevops\Build\Dockerfile\DockerfileGenerator;
use Webdevops\Build\FileReader;
use Webdevops\Build\Tests\TemporaryRepository;

use function file_get_contents;
use function readlink;
use function unlink;

#[CoversClass(DockerfileGenerator::class)]
final class DockerfileGeneratorTest extends TestCase
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

    /**
     * The Dockerfiles of the test repository were rendered by Jinja from the templates before they became Twig templates;
     * only docker/base/alpine lost the trailing space after its FROM line, when the comment there moved above it.
     * docker/base/alpine uses a comment, if/elseif/else, a macro that imports another file and a macro whose output
     * starts with a line break.
     */
    public function testRendersTheDockerfiles(): void
    {
        $dockerfiles = new FileReader($this->repository->path)->collectDockerfiles();
        foreach ($dockerfiles as $dockerfile) {
            $this->repository->write($dockerfile, '');
        }

        $this->generate();

        self::assertCount(9, $dockerfiles);
        foreach ($dockerfiles as $dockerfile) {
            self::assertSame(file_get_contents(TemporaryRepository::FIXTURE . '/' . $dockerfile), $this->repository->read($dockerfile), $dockerfile);
        }
    }

    public function testLogsEachDockerfileAndLeavesOutLinkedDirectories(): void
    {
        // docker/php/8.4 links to docker/php-official/8.4
        self::assertSame([
            'webdevops/base-app:ubuntu-22.04',
            'webdevops/base:alpine',
            'webdevops/base:ubuntu-22.04',
            'webdevops/php-nginx-dev:8.5-alpine',
            'webdevops/php-nginx:8.5-alpine',
            'webdevops/php-official:8.4',
            'webdevops/php-official:8.5-alpine',
            'webdevops/php-official:8.5',
            'webdevops/toolbox:latest',
        ], $this->generate());
    }

    public function testTemplatesOutsideAnImageTagDirectoryAreLeftOut(): void
    {
        $this->repository->write('docker/base/Dockerfile.twig', '{{ undefined.macro() }}');
        $this->repository->write('docker/base/alpine/conf/Dockerfile.twig', '{{ undefined.macro() }}');

        self::assertCount(9, $this->generate());
    }

    public function testUnknownMacroIsAnError(): void
    {
        $this->repository->write('docker/base/alpine/Dockerfile.twig', "{% import 'docker.twig' as docker %}\n{{ docker.missing() }}\n");

        $this->expectException(RuntimeError::class);
        $this->generate();
    }

    public function testUnknownVariableIsAnError(): void
    {
        $this->repository->write('docker/base/alpine/Dockerfile.twig', "{{ Dockerfile.version }}\n");

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Key "version" for sequence/mapping with keys "image, tag" does not exist');
        $this->generate();
    }

    public function testDirectoryWithoutTemplateIsAnError(): void
    {
        // e.g. the template was removed to stop building the tag, but not the generated files
        $this->repository->write('docker/base/alpine/Dockerfile', '');
        $this->repository->write('docker/base/alpine-edge/Dockerfile', "# Dockerfile for webdevops/base:alpine-edge\n");
        $this->repository->write('docker/php-official/8.3/conf/etc/php.conf', "php\n");

        try {
            $this->generate();
            self::fail('the directories without template were accepted');
        } catch (RuntimeException $runtimeException) {
            self::assertSame(
                'No Dockerfile.twig in docker/base/alpine-edge, docker/php-official/8.3. '
                . 'Add the template, or remove the directory to stop building the tag.',
                $runtimeException->getMessage(),
            );
            self::assertSame('', $this->repository->read('docker/base/alpine/Dockerfile'), 'checked before any Dockerfile is written');
        }
    }

    public function testRemovesTheFilesOfTheFinder(): void
    {
        // a directory of a removed tag that only the Finder's file keeps
        $this->repository->write('docker/base/alpine-old/.DS_Store', '');
        $this->repository->write('docker/base/alpine/.DS_Store', '');
        $this->repository->write('docker/.DS_Store', '');
        $this->repository->write('docker/toolbox/latest/conf/.DS_Store', '');

        $this->generate();

        self::assertFileDoesNotExist($this->repository->path . '/docker/base/alpine-old/.DS_Store');
        self::assertFileDoesNotExist($this->repository->path . '/docker/base/alpine/.DS_Store');
        self::assertFileDoesNotExist($this->repository->path . '/docker/.DS_Store');
        self::assertFileDoesNotExist($this->repository->path . '/docker/toolbox/latest/conf/.DS_Store', 'it would get into a local build');
    }

    public function testUpdatesTheLinksOfPhp(): void
    {
        unlink($this->repository->path . '/docker/php/8.5');

        $this->generate();

        self::assertSame('../php-official/8.5', readlink($this->repository->path . '/docker/php/8.5'));
    }

    public function testNewTagIsRendered(): void
    {
        $this->repository->write('docker/base/alpine-edge/Dockerfile.twig', $this->repository->read('docker/base/alpine/Dockerfile.twig'));

        self::assertContains('webdevops/base:alpine-edge', $this->generate());
        self::assertStringStartsWith(
            "#+++++++++++++++++++++++++++++++++++++++\n# Dockerfile for webdevops/base:alpine-edge\n",
            $this->repository->read('docker/base/alpine-edge/Dockerfile'),
        );
    }

    /**
     * @return list<string> the log lines
     */
    private function generate(): array
    {
        $lines = [];
        new DockerfileGenerator($this->repository->path)->generate(function (string $line) use (&$lines): void {
            $lines[] = $line;
        });
        return $lines;
    }
}
