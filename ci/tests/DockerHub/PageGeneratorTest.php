<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\DockerHub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\DockerHub\Config;
use Webdevops\Build\DockerHub\PageGenerator;
use Webdevops\Build\Tests\TemporaryRepository;

use function file_exists;
use function str_repeat;
use function str_replace;

#[CoversClass(PageGenerator::class)]
final class PageGeneratorTest extends TestCase
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

    public function testWritesAPagePerRepository(): void
    {
        self::assertSame([
            'written' => [
                'docs/dockerhub/base.md',
                'docs/dockerhub/base-app.md',
                'docs/dockerhub/hhvm.md',
                'docs/dockerhub/nginx.md',
                'docs/dockerhub/php.md',
                'docs/dockerhub/php-nginx.md',
                'docs/dockerhub/php-nginx-dev.md',
                'docs/dockerhub/php-official.md',
                'docs/dockerhub/toolbox.md',
            ],
            'removed' => [],
        ], new PageGenerator($this->repository->path)->generate());
    }

    public function testBuiltPage(): void
    {
        new PageGenerator($this->repository->path)->generate();

        self::assertSame(<<<'MARKDOWN'
            # webdevops/php

            PHP-FPM on top of the official PHP image.

            Rebuilt every night for linux/amd64 and linux/arm64.

            ## Tags

            | Tag | Based on |
            | --- | --- |
            | `8.5` | Debian 13 |
            | `8.4` | Debian 12 |
            | `8.5-alpine` | Alpine |

            ## Quick start

            ```yaml
            services:
              php:
                image: webdevops/php:8.5
            ```

            ## Documentation

            - [php and php-dev](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md)

            Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)

            MARKDOWN, $this->repository->read('docs/dockerhub/php.md'));
    }

    public function testPageWithoutExampleHasNoQuickStart(): void
    {
        new PageGenerator($this->repository->path)->generate();

        self::assertSame(<<<'MARKDOWN'
            # webdevops/base

            Entrypoint and supervisord, see [Services](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md#services).

            Rebuilt every night for linux/amd64 and linux/arm64.

            ## Tags

            | Tag | Based on |
            | --- | --- |
            | `ubuntu-22.04`, `latest` | Ubuntu 22.04 |
            | `alpine` | Alpine 3.22 |

            ## Documentation

            - [base](https://github.com/webdevops/Dockerfile/blob/master/docs/images/base.md)

            Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)

            MARKDOWN, $this->repository->read('docs/dockerhub/base.md'));
    }

    public function testDevelopmentPageAndNote(): void
    {
        new PageGenerator($this->repository->path)->generate();

        self::assertStringStartsWith(<<<'MARKDOWN'
            # webdevops/php-nginx-dev

            php-nginx with Xdebug.

            Meant for local development. Do not use it in production.

            The `alpine` tag only.

            Rebuilt every night for linux/amd64 and linux/arm64.

            MARKDOWN, $this->repository->read('docs/dockerhub/php-nginx-dev.md'));
    }

    public function testDiscontinuedPages(): void
    {
        new PageGenerator($this->repository->path)->generate();

        self::assertSame(<<<'MARKDOWN'
            # webdevops/nginx

            > **This image is no longer built.** The last image is from November 2026.
            > It gets no updates, including security fixes.

            Use [`nginx:alpine`](https://hub.docker.com/_/nginx) instead. How to switch: [Replacing discontinued images](https://github.com/webdevops/Dockerfile/blob/master/docs/discontinued-images.md#webdevopsnginx).

            Source: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)

            MARKDOWN, $this->repository->read('docs/dockerhub/nginx.md'));
        self::assertSame(<<<'MARKDOWN'
            # webdevops/hhvm

            > **This image is no longer built.** The last image is from April 2020.
            > It gets no updates, including security fixes.

            Source: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)

            MARKDOWN, $this->repository->read('docs/dockerhub/hhvm.md'));
    }

    public function testBuiltImageWithoutEntryIsAnError(): void
    {
        $this->replaceInConfig("  toolbox:\n    short: 'Build helper'\n    intro: 'Stores the helper scripts.'\n    docs:\n      - { label: 'Images', path: 'docs/images/base.md' }\n", '');

        try {
            new PageGenerator($this->repository->path)->generate();
            self::fail('The missing entry was not reported.');
        } catch (RuntimeException $exception) {
            self::assertSame('webdevops/toolbox is built but has no entry under "built" in conf/dockerhub.yml.', $exception->getMessage());
        }

        self::assertFalse(file_exists($this->repository->path . '/docs/dockerhub/php.md'), 'a failed check writes no page');
    }

    public function testEntryOfAnImageThatIsNotBuiltIsAnError(): void
    {
        $this->replaceInConfig("built:\n", "built:\n  apache:\n    short: 'Apache'\n    intro: 'Apache.'\n    docs:\n      - { label: 'base', path: 'docs/images/base.md' }\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('webdevops/apache has an entry under "built" in conf/dockerhub.yml but is not built any more. Move it to "discontinued".');
        new PageGenerator($this->repository->path)->generate();
    }

    public function testPageLargerThanTheLimitIsAnError(): void
    {
        $this->replaceInConfig("intro: 'PHP-FPM on top of the official PHP image.'", "intro: '" . str_repeat('a', 25000) . "'");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^docs\/dockerhub\/php\.md has \d+ bytes; Docker Hub takes at most 25000\.$/');
        new PageGenerator($this->repository->path)->generate();
    }

    public function testBrokenLinkIsAnError(): void
    {
        $this->replaceInConfig("path: 'docs/images/php.md#development-variant-php-dev'", "path: 'docs/images/php.md#development-variant'");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docs/images/php.md#development-variant in conf/dockerhub.yml: docs/images/php.md has no heading with the anchor #development-variant.');
        new PageGenerator($this->repository->path)->generate();
    }

    public function testRemovesPagesWithoutEntry(): void
    {
        $this->repository->write('docs/dockerhub/old.md', "# webdevops/old\n");
        $this->repository->write('docs/dockerhub/notes.txt', "kept\n");

        $result = new PageGenerator($this->repository->path)->generate();

        self::assertSame(['docs/dockerhub/old.md'], $result['removed']);
        self::assertFalse(file_exists($this->repository->path . '/docs/dockerhub/old.md'));
        self::assertTrue(file_exists($this->repository->path . '/docs/dockerhub/notes.txt'), 'only pages are removed');
    }

    private function replaceInConfig(string $search, string $replace): void
    {
        $content = $this->repository->read(Config::FILE);
        self::assertStringContainsString($search, $content);
        $this->repository->write(Config::FILE, str_replace($search, $replace, $content));
    }
}
