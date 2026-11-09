<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\ReadmeBanner\BannerRenderer;
use Webdevops\Build\ReadmeBanner\GlyphFont;
use Webdevops\Build\ReadmeBanner\ImageMatrix;
use Webdevops\Build\Tests\TemporaryRepository;

use function count;
use function file_get_contents;
use function json_decode;
use function str_repeat;

use const JSON_THROW_ON_ERROR;

#[CoversClass(BannerRenderer::class)]
final class BannerRendererTest extends TestCase
{
    private const array PLATFORMS = ['linux/arm64', 'linux/amd64'];

    /** @var array<string, GlyphFont> */
    private static array $fonts = [];

    public static function setUpBeforeClass(): void
    {
        $table = json_decode((string)file_get_contents(TemporaryRepository::GLYPHS), true, 512, JSON_THROW_ON_ERROR);
        foreach ($table as $name => $font) {
            self::$fonts[$name] = GlyphFont::fromTable($name, $font);
        }
    }

    public function testRendersAnSvgWithTitleAndDescription(): void
    {
        $svg = $this->render('light');
        $xml = new SimpleXMLElement($svg);

        self::assertSame('1280', (string)$xml['width']);
        // The base OS card is the highest: two rows of chips with the two release notes between them
        self::assertSame('574', (string)$xml['height']);
        self::assertSame('Test banner', (string)$xml->title);
        self::assertSame(
            'Image tag anatomy using webdevops/php-nginx-dev:8.5-alpine as the example. '
            . 'Web server: php (PHP-FPM) or php-nginx (+ Nginx). Variant: no suffix for production, -dev for development. '
            . 'PHP version: 8.4 or 8.5. Base OS: no suffix for Debian (bookworm: 8.4; trixie: 8.5), -alpine for Alpine. '
            . 'Extends the official php images. linux/amd64 + linux/arm64.',
            (string)$xml->desc,
        );
        self::assertStringEndsWith("</svg>\n", $svg);
    }

    public function testOneReleaseIsPartOfTheText(): void
    {
        $matrix = new ImageMatrix([
            ...$this->images(),
            ['image' => 'php', 'tag' => '8.5', 'from' => 'php:8.5-fpm-bookworm'],
        ], self::PLATFORMS);

        self::assertStringContainsString('Base OS: no suffix for Debian bookworm, -alpine for Alpine.', $this->render('light', matrix: $matrix));
    }

    public function testThemesUseTheirColors(): void
    {
        self::assertStringContainsString('fill="' . BannerRenderer::THEMES['light']['background'] . '"', $this->render('light'));
        self::assertStringContainsString('fill="' . BannerRenderer::THEMES['dark']['background'] . '"', $this->render('dark'));
        self::assertStringNotContainsString(BannerRenderer::THEMES['dark']['background'], $this->render('light'));
    }

    public function testDrawsEveryTextAsPath(): void
    {
        $xml = new SimpleXMLElement($this->render('dark'));
        $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

        self::assertSame([], $xml->xpath('//svg:text'));
        self::assertGreaterThan(30, count($xml->xpath('//svg:path') ?: []));
    }

    public function testRenderingIsTheSameEveryTime(): void
    {
        self::assertSame($this->render('light'), $this->render('light'));
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidTexts(): iterable
    {
        yield 'flavor without description' => [
            ['flavors' => ['php' => 'PHP-FPM']],
            'The README banner has no description for the image "php-nginx". Add one under "flavors" in conf/readme-banner.yml.',
        ];
        yield 'suffix without description' => [
            ['bases' => ['default' => 'Debian {release}', 'releases' => '{release}: {versions}', 'suffixes' => []]],
            'The README banner has no description for the tag suffix "-alpine". Add one under "bases: suffixes" in conf/readme-banner.yml.',
        ];
        yield 'example image that is not built' => [
            ['example' => ['flavor' => 'php-apache', 'base' => 'alpine']],
            'The README banner example uses "php-apache", which is not built. Change "example" in conf/readme-banner.yml.',
        ];
        yield 'example without dev image' => [
            ['example' => ['flavor' => 'php', 'base' => 'alpine']],
            'The README banner example needs webdevops/php-dev images with the tag suffix "-alpine". Change "example" in conf/readme-banner.yml.',
        ];
        yield 'example without suffix' => [
            ['example' => ['flavor' => 'php-nginx', 'base' => '']],
            'The README banner example needs webdevops/php-nginx-dev images with the tag suffix "-". Change "example" in conf/readme-banner.yml.',
        ];
        yield 'missing setting' => [
            ['cards' => ['flavor' => 'WEB SERVER']],
            'conf/readme-banner.yml has no setting "cards: variant".',
        ];
        yield 'setting that is no text' => [
            ['title' => ['a', 'b']],
            'The setting "title" in conf/readme-banner.yml must be a text.',
        ];
        yield 'text that does not fit' => [
            ['footer' => [str_repeat('Rebuilt nightly ', 20)]],
            'The README banner has no room for the footer (',
        ];
        yield 'character without glyph' => [
            ['heading' => 'PICK YOUR IMAGE ✓'],
            'The banner font "sans-semibold" has no glyph for "✓"',
        ];
    }

    /**
     * @param array<string, mixed> $texts
     */
    #[DataProvider('invalidTexts')]
    public function testInvalidTextsAreReported(array $texts, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->render('light', $texts);
    }

    /**
     * @param array<string, mixed> $texts replaces settings of the fixture's conf/readme-banner.yml
     */
    private function render(string $theme, array $texts = [], ?ImageMatrix $matrix = null): string
    {
        $texts = [...Yaml::parseFile(TemporaryRepository::FIXTURE . '/conf/readme-banner.yml'), ...$texts];
        return new BannerRenderer($matrix ?? new ImageMatrix($this->images(), self::PLATFORMS), $texts, self::$fonts)->render($theme);
    }

    /**
     * The PHP images of the fixture repository: 8.4 on bookworm, 8.5 on trixie and alpine.
     *
     * @return list<array{image: string, tag: string, from: ?string}>
     */
    private function images(): array
    {
        return [
            ['image' => 'php', 'tag' => '8.4', 'from' => 'php:8.4-fpm-bookworm'],
            ['image' => 'php', 'tag' => '8.5', 'from' => 'php:8.5-fpm-trixie'],
            ['image' => 'php', 'tag' => '8.5-alpine', 'from' => 'php:8.5-fpm-alpine'],
            ['image' => 'php-nginx', 'tag' => '8.5-alpine', 'from' => 'webdevops/php:8.5-alpine'],
            ['image' => 'php-nginx-dev', 'tag' => '8.5-alpine', 'from' => 'webdevops/php-nginx:8.5-alpine'],
            ['image' => 'base', 'tag' => 'ubuntu-22.04', 'from' => 'ubuntu:22.04'],
        ];
    }
}
