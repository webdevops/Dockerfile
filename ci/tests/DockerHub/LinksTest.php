<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\DockerHub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\DockerHub\Links;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(Links::class)]
final class LinksTest extends TestCase
{
    public function testUrlOfAPathAndAnAnchor(): void
    {
        $links = new Links(TemporaryRepository::FIXTURE);

        self::assertSame(
            'https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md#development-variant-php-dev',
            $links->url('docs/images/php.md#development-variant-php-dev'),
        );
        self::assertSame('https://github.com/webdevops/Dockerfile/blob/master/docs/images/base.md', $links->url('docs/images/base.md'));
        self::assertSame('https://hub.docker.com/_/nginx', $links->url('https://hub.docker.com/_/nginx'));
    }

    public function testMissingFileIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docs/nope.md in conf/dockerhub.yml: docs/nope.md does not exist.');
        new Links(TemporaryRepository::FIXTURE)->url('docs/nope.md');
    }

    public function testDirectoryIsNotAFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docs/images in conf/dockerhub.yml: docs/images does not exist.');
        new Links(TemporaryRepository::FIXTURE)->url('docs/images');
    }

    public function testMissingAnchorIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docs/images/php.md#fenced in conf/dockerhub.yml: docs/images/php.md has no heading with the anchor #fenced.');
        new Links(TemporaryRepository::FIXTURE)->url('docs/images/php.md#fenced');
    }

    public function testRewrite(): void
    {
        self::assertSame(
            'See [Services](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md#services) and [Hub](https://hub.docker.com).',
            new Links(TemporaryRepository::FIXTURE)->rewrite('See [Services](docs/customization.md#services) and [Hub](https://hub.docker.com).'),
        );
    }

    public function testRewriteChecksTheTargets(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docs/customization.md#cron in conf/dockerhub.yml: docs/customization.md has no heading with the anchor #cron.');
        new Links(TemporaryRepository::FIXTURE)->rewrite('See [Cron](docs/customization.md#cron).');
    }
}
