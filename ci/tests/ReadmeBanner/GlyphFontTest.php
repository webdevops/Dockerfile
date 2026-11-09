<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\GlyphFont;

#[CoversClass(GlyphFont::class)]
final class GlyphFontTest extends TestCase
{
    public function testWidthAddsAdvancesKerningAndTracking(): void
    {
        $font = $this->font();

        self::assertSame(0.0, $font->width('', 10));
        self::assertSame(6.0, $font->width('A', 10));
        self::assertSame((600 - 50 + 600) / 100, $font->width('AV', 10));
        self::assertSame(12.0, $font->width('VA', 10), 'only AV kerns');
        self::assertSame((600 - 50 + 600 + 250) / 100 + 2 * 1.5, $font->width('AV ', 10, 1.5));
    }

    public function testPathPlacesEachGlyphOnTheBaseline(): void
    {
        // y points down in SVG, so the outline is flipped; the space has no outline
        self::assertSame(
            'M 10 100 L 16 100 L 13 93 Z M 15.5 93 L 18.5 100 L 21.5 93 Z M 24 100 L 30 100 L 27 93 Z',
            $this->font()->path('AV A', 10, 100, 10),
        );
    }

    public function testPathWithTracking(): void
    {
        self::assertSame('M 10 100 L 16 100 L 13 93 Z M 18.5 93 L 21.5 100 L 24.5 93 Z', $this->font()->path('AV', 10, 100, 10, 3));
    }

    public function testPathAnchoredInTheMiddleOrAtTheEnd(): void
    {
        $font = $this->font();

        self::assertSame($font->path('A', 7, 100, 10), $font->path('A', 10, 100, 10, 0, 'middle'));
        self::assertSame($font->path('A', 4, 100, 10), $font->path('A', 10, 100, 10, 0, 'end'));
    }

    public function testMissingCharacterIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The banner font "test" has no glyph for "ä" (U+00E4) in "Bär".');
        $this->font()->width('Bär', 10);
    }

    private function font(): GlyphFont
    {
        return GlyphFont::fromTable('test', [
            'unitsPerEm' => 1000,
            'glyphs' => [
                'A' => [600, 'M 0 0 L 600 0 L 300 700 Z'],
                'V' => [600, 'M 0 700 L 300 0 L 600 700 Z'],
                'B' => [600, 'M 0 0 L 600 0 L 600 700 Z'],
                'r' => [400, 'M 0 0 L 400 0 L 400 500 Z'],
                ' ' => [250, ''],
            ],
            'kerning' => ['AV' => -50],
        ]);
    }
}
