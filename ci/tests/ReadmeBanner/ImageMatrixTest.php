<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\ImageMatrix;

#[CoversClass(ImageMatrix::class)]
final class ImageMatrixTest extends TestCase
{
    public function testFlavorsArePhpFirstAndThenAlphabetical(): void
    {
        $matrix = new ImageMatrix([
            $this->image('php-nginx-dev', '8.5'),
            $this->image('php-nginx', '8.5'),
            $this->image('php', '8.5', 'php:8.5-fpm-bookworm'),
            $this->image('php-apache', '8.5'),
            $this->image('base', 'ubuntu-22.04'),
            $this->image('toolbox', 'latest'),
        ], []);

        self::assertSame(['php', 'php-apache', 'php-nginx'], $matrix->flavors());
    }

    public function testVersionsAreSortedAsVersions(): void
    {
        $matrix = new ImageMatrix([
            $this->image('php', '8.10', 'php:8.10-fpm-bookworm'),
            $this->image('php', '8.4', 'php:8.4-fpm-bookworm'),
            $this->image('php-nginx', '8.9-alpine'),
            $this->image('php', '7.4', 'php:7.4-fpm-bullseye'),
        ], []);

        self::assertSame(['7.4', '8.4', '8.9', '8.10'], $matrix->versions());
    }

    public function testNewestVersionOfAnImageWithASuffix(): void
    {
        $matrix = new ImageMatrix([
            $this->image('php', '8.5', 'php:8.5-fpm-bookworm'),
            $this->image('php-nginx-dev', '8.10-alpine'),
            $this->image('php-nginx-dev', '8.9-alpine'),
            $this->image('php-nginx-dev', '8.11'),
        ], []);

        self::assertSame('8.10', $matrix->newestVersion('php-nginx-dev', 'alpine'));
        self::assertSame('8.11', $matrix->newestVersion('php-nginx-dev', ImageMatrix::NO_SUFFIX));
        self::assertNull($matrix->newestVersion('php-nginx-dev', 'trixie'));
        self::assertNull($matrix->newestVersion('php-apache-dev', 'alpine'));
    }

    public function testBaseSuffixesAreSortedWithoutSuffixFirst(): void
    {
        $matrix = new ImageMatrix([
            $this->image('php', '8.5-alpine', 'php:8.5-fpm-alpine'),
            $this->image('php', '8.5', 'php:8.5-fpm-bookworm'),
            $this->image('php-nginx', '8.5-1'),
            $this->image('php-nginx', '8.5-alpine'),
        ], []);

        self::assertSame([ImageMatrix::NO_SUFFIX, '1', 'alpine'], $matrix->baseSuffixes());
    }

    public function testReleasesOfThePhpImagesOldestVersionFirst(): void
    {
        $matrix = new ImageMatrix([
            $this->image('php', '8.5', 'php:8.5-fpm-trixie'),
            $this->image('php', '8.4', 'php:8.4-fpm-bookworm'),
            $this->image('php', '8.4-alpine', 'php:8.4-fpm-alpine3.22'),
            $this->image('php-nginx', '8.3', 'webdevops/php:8.3'),
        ], []);

        self::assertSame(['8.4' => 'bookworm', '8.5' => 'trixie'], $matrix->releases(ImageMatrix::NO_SUFFIX));
        self::assertSame(['8.4' => 'alpine3.22'], $matrix->releases('alpine'));
        self::assertSame([], $matrix->releases('trixie'));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function baseImages(): iterable
    {
        yield 'variant and release' => ['php:8.5-fpm-bookworm', 'bookworm'];
        yield 'release only' => ['php:8.5-trixie', 'trixie'];
        yield 'registry with a port' => ['registry.example.com:5000/library/php:8.5-zts-bookworm', 'bookworm'];
        yield 'digest' => ['php:8.5-cli-alpine3.22@sha256:0123456789abcdef', 'alpine3.22'];
    }

    #[DataProvider('baseImages')]
    public function testReleaseIsTheLastPartOfTheTag(string $baseImage, string $release): void
    {
        $matrix = new ImageMatrix([$this->image('php', '8.5', $baseImage)], []);

        self::assertSame(['8.5' => $release], $matrix->releases(ImageMatrix::NO_SUFFIX));
    }

    /**
     * @return iterable<string, array{0: ?string}>
     */
    public static function baseImagesWithoutRelease(): iterable
    {
        yield 'variant only' => ['php:8.5-fpm'];
        yield 'version only' => ['php:8.5'];
        yield 'no tag' => ['php'];
        yield 'unknown' => [null];
    }

    #[DataProvider('baseImagesWithoutRelease')]
    public function testBaseImageWithoutReleaseIsAnError(?string $baseImage): void
    {
        $matrix = new ImageMatrix([$this->image('php', '8.5', $baseImage)], []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The README banner found no release in "' . $baseImage . '", the image webdevops/php:8.5 is built from. Name it in the tag, e.g. php:8.5-fpm-bookworm.',
        );
        $matrix->releases(ImageMatrix::NO_SUFFIX);
    }

    public function testPlatformsAreSorted(): void
    {
        $matrix = new ImageMatrix([$this->image('php', '8.5')], ['linux/arm64', 'linux/amd64']);

        self::assertSame(['linux/amd64', 'linux/arm64'], $matrix->platforms());
    }

    public function testTagWithoutPhpVersionIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The README banner cannot take apart the tag webdevops/php-nginx:latest. It expects the PHP version and an optional suffix, e.g. 8.5 or 8.5-alpine.',
        );
        new ImageMatrix([$this->image('php', '8.5'), $this->image('php-nginx', 'latest')], []);
    }

    public function testWebdevopsPhpIsNeeded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The README banner found no webdevops/php images to show.');
        new ImageMatrix([$this->image('php-nginx', '8.5'), $this->image('base', 'ubuntu-22.04')], []);
    }

    /**
     * @return array{image: string, tag: string, from: ?string}
     */
    private function image(string $image, string $tag, ?string $from = null): array
    {
        return ['image' => $image, 'tag' => $tag, 'from' => $from];
    }
}
