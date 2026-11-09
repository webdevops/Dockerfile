<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\BannerGenerator;
use Webdevops\Build\Tests\TemporaryRepository;

use function json_decode;
use function json_encode;
use function str_replace;

use const JSON_THROW_ON_ERROR;

#[CoversClass(BannerGenerator::class)]
final class BannerGeneratorTest extends TestCase
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

    public function testWritesTheFileOfEachTheme(): void
    {
        $written = new BannerGenerator($this->repository->path)->generate($this->images(), ['linux/amd64']);

        self::assertSame(['docs/assets/banner-light.svg', 'docs/assets/banner-dark.svg'], $written);
        self::assertStringContainsString('#f6f8fa', $this->repository->read('docs/assets/banner-light.svg'));
        self::assertStringContainsString('#151b23', $this->repository->read('docs/assets/banner-dark.svg'));
        self::assertStringContainsString('webdevops/php-nginx-dev:8.5-alpine', $this->repository->read('docs/assets/banner-dark.svg'));
    }

    public function testEveryThemeNeedsExactlyOneFile(): void
    {
        $config = $this->repository->read('conf/readme-banner.yml');
        $this->repository->write('conf/readme-banner.yml', str_replace("  dark: 'docs/assets/banner-dark.svg'\n", "  sepia: 'docs/assets/banner-sepia.svg'\n", $config));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('conf/readme-banner.yml needs a file for each of the themes light and dark under "files".');
        new BannerGenerator($this->repository->path)->generate($this->images(), ['linux/amd64']);
    }

    public function testGlyphTableNeedsEveryFont(): void
    {
        $table = json_decode($this->repository->read('ci/resources/readme-banner/glyphs.json'), true, 512, JSON_THROW_ON_ERROR);
        unset($table['mono-medium']);
        $this->repository->write('ci/resources/readme-banner/glyphs.json', (string)json_encode($table));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ci/resources/readme-banner/glyphs.json has no font "mono-medium". Build it again with ci/console readme-banner:build-glyphs');
        new BannerGenerator($this->repository->path)->generate($this->images(), ['linux/amd64']);
    }

    /**
     * @return list<array{image: string, tag: string, from: ?string}>
     */
    private function images(): array
    {
        return [
            ['image' => 'php', 'tag' => '8.5', 'from' => 'php:8.5-fpm-trixie'],
            ['image' => 'php', 'tag' => '8.5-alpine', 'from' => 'php:8.5-fpm-alpine'],
            ['image' => 'php-nginx-dev', 'tag' => '8.5-alpine', 'from' => 'webdevops/php-nginx:8.5-alpine'],
        ];
    }
}
