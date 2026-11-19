<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\DockerHub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\DockerHub\PublishedTags;
use Webdevops\Build\FileReader;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(PublishedTags::class)]
final class PublishedTagsTest extends TestCase
{
    private const array RELEASES = ['bookworm' => 'Debian 12', 'trixie' => 'Debian 13'];

    public function testRowsPerRepository(): void
    {
        $php = [
            ['base' => 'Debian 13', 'tags' => ['8.5']],
            ['base' => 'Debian 12', 'tags' => ['8.4']],
            ['base' => 'Alpine', 'tags' => ['8.5-alpine']],
        ];

        self::assertSame([
            'base' => [
                ['base' => 'Ubuntu 22.04', 'tags' => ['ubuntu-22.04', 'latest']],
                ['base' => 'Alpine 3.22', 'tags' => ['alpine']],
            ],
            // FROM webdevops/base:latest, the alias of webdevops/base:ubuntu-22.04
            'base-app' => [['base' => 'Ubuntu 22.04', 'tags' => ['ubuntu-22.04', 'latest']]],
            'php' => $php,
            'php-nginx' => [['base' => 'Alpine', 'tags' => ['8.5-alpine']]],
            'php-nginx-dev' => [['base' => 'Alpine', 'tags' => ['8.5-alpine']]],
            'php-official' => $php,
            'toolbox' => [['base' => 'Alpine 3.22', 'tags' => ['latest']]],
        ], new PublishedTags(new FileReader(TemporaryRepository::FIXTURE), self::RELEASES)->byRepository());
    }

    public function testUnknownReleaseIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot name the base image php:8.5-fpm-trixie of webdevops/php:8.5 for its Docker Hub page. Add its release to "releases" in conf/dockerhub.yml.',
        );
        new PublishedTags(new FileReader(TemporaryRepository::FIXTURE), ['bookworm' => 'Debian 12'])->byRepository();
    }

    public function testOtherBaseImagesAreNamed(): void
    {
        $repository = new TemporaryRepository();
        try {
            $repository->write('docker/toolbox/latest/Dockerfile', "# Dockerfile for webdevops/toolbox:latest\nFROM alpine:latest\n");
            $repository->write('docker/base/alpine/Dockerfile', "# Dockerfile for webdevops/base:alpine\nFROM debian:bookworm\n");
            $repository->write('docker/php-official/8.4/Dockerfile', "# Dockerfile for webdevops/php-official:8.4\nFROM php:8.4-fpm-alpine3.24\n");
            $byRepository = new PublishedTags(new FileReader($repository->path), self::RELEASES)->byRepository();
        } finally {
            $repository->remove();
        }

        self::assertSame([['base' => 'Alpine', 'tags' => ['latest']]], $byRepository['toolbox']);
        self::assertSame(['base' => 'Debian 12', 'tags' => ['alpine']], $byRepository['base'][1]);
        self::assertSame(['base' => 'Alpine 3.24', 'tags' => ['8.4']], $byRepository['php'][1]);
    }
}
