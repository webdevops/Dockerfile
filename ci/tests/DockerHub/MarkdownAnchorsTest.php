<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\DockerHub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\DockerHub\MarkdownAnchors;

#[CoversClass(MarkdownAnchors::class)]
final class MarkdownAnchorsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function headings(): iterable
    {
        yield 'slash' => ['## webdevops/nginx', 'webdevopsnginx'];
        yield 'code and parentheses' => ['## Development variant (`php-dev`)', 'development-variant-php-dev'];
        yield 'commas' => ['## Alpine tags of bootstrap, base and base-app', 'alpine-tags-of-bootstrap-base-and-base-app'];
        yield 'link' => ['### See [Mail](#mail)', 'see-mail'];
        yield 'umlaut' => ['## Größe', 'größe'];
        yield 'underscore and dot' => ['# PHP_DEBUGGER and php.ini', 'php_debugger-and-phpini'];
    }

    #[DataProvider('headings')]
    public function testAnchorsLikeGithub(string $heading, string $anchor): void
    {
        self::assertSame([$anchor], MarkdownAnchors::of("Text\n\n" . $heading . "\n\nMore text\n"));
    }

    public function testDuplicatesGetANumber(): void
    {
        self::assertSame(['usage', 'usage-1', 'usage-2'], MarkdownAnchors::of("## Usage\n\n## Usage\n\n### Usage\n"));
    }

    public function testHeadingsInCodeFencesAreIgnored(): void
    {
        self::assertSame(
            ['before', 'after'],
            MarkdownAnchors::of("## Before\n\n```markdown\n## Fenced\n```\n\n~~~\n# Also fenced\n```\n~~~\n\n## After\n"),
        );
    }

    public function testOnlyAtxHeadings(): void
    {
        self::assertSame(['title'], MarkdownAnchors::of("# Title\n\n#hashtag\n    ## indented code\n"));
    }
}
