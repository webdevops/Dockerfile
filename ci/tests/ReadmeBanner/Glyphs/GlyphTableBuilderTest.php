<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\GlyphFont;
use Webdevops\Build\ReadmeBanner\Glyphs\GlyphTableBuilder;

use function array_combine;
use function array_fill_keys;
use function array_keys;
use function array_map;
use function implode;
use function json_decode;
use function mb_ord;
use function unlink;

use const JSON_THROW_ON_ERROR;

#[CoversClass(GlyphTableBuilder::class)]
final class GlyphTableBuilderTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testCharactersArePrintableAsciiAndSomeTypography(): void
    {
        $characters = GlyphTableBuilder::characters();

        self::assertCount(95 + 8, $characters);
        self::assertSame(' ', $characters[0]);
        self::assertSame('~', $characters[94]);
        self::assertSame('·–—…‘’“”', implode('', array_map(fn (int $i): string => $characters[$i], [95, 96, 97, 98, 99, 100, 101, 102])));
    }

    public function testWritesOneTableForEachFont(): void
    {
        $fonts = array_combine(GlyphFont::FONTS, array_map(fn (string $key): string => $this->font(), GlyphFont::FONTS));

        $json = new GlyphTableBuilder()->build($fonts);
        $table = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(GlyphFont::FONTS, array_keys($table));
        self::assertSame('JetBrains Mono, SIL Open Font License 1.1, see OFL-JetBrainsMono.txt', $table['mono-bold']['source']);
        self::assertSame('Inter, SIL Open Font License 1.1, see OFL-Inter.txt', $table['sans-regular']['source']);
        self::assertSame(2048, $table['sans-regular']['unitsPerEm']);
        self::assertSame(GlyphTableBuilder::characters(), array_map(strval(...), array_keys($table['sans-regular']['glyphs'])));
        self::assertSame([600, 'M 100 0 L 500 0 L 500 700 L 100 700 Z'], $table['sans-regular']['glyphs']['A']);
        self::assertSame([250, ''], $table['sans-regular']['glyphs'][' ']);
        self::assertSame(['AV' => -80, 'VA' => -60], $table['sans-regular']['kerning']);
        self::assertStringContainsString("\n      \"A\": [600, \"M 100 0 L 500 0 L 500 700 L 100 700 Z\"],\n", $json, 'one glyph per line');
        self::assertStringContainsString('"“": [250, ""]', $json, 'Unicode is not escaped');

        // GlyphFont reads the table back
        $font = GlyphFont::fromTable('sans-regular', $table['sans-regular']);
        self::assertSame((600 - 80 + 600) / 2048 * 10, $font->width('AV', 10));
    }

    public function testEveryFontIsNeeded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The README banner draws with the fonts mono-bold, mono-medium, sans-regular, sans-medium, sans-semibold. Add mono-medium=<file>, sans-semibold=<file>.');
        new GlyphTableBuilder()->build(['mono-bold' => 'a.ttf', 'sans-regular' => 'b.ttf', 'sans-medium' => 'c.ttf']);
    }

    public function testFontKeyNamesTheFamily(): void
    {
        $fonts = array_fill_keys(GlyphFont::FONTS, 'font.ttf');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The font key "serif-bold" has to start with "mono-" or "sans-".');
        new GlyphTableBuilder()->build(['serif-bold' => 'font.ttf', ...$fonts]);
    }

    public function testEveryCharacterNeedsAGlyph(): void
    {
        $path = $this->font(withoutCharacter: '~');
        $fonts = array_fill_keys(GlyphFont::FONTS, $path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' has no glyph for "~".');
        new GlyphTableBuilder()->build($fonts);
    }

    /**
     * A font with a square for A and V, which kern, and an empty glyph for every other character.
     */
    private function font(?string $withoutCharacter = null): string
    {
        $builder = new FontBuilder(2048);
        $empty = $builder->emptyGlyph(250);
        $a = $builder->simpleGlyph(600, [[[100, 0], [500, 0], [500, 700], [100, 700]]]);
        $v = $builder->simpleGlyph(600, [[[100, 0], [500, 0], [500, 700], [100, 700]]]);
        foreach (GlyphTableBuilder::characters() as $character) {
            if ($character !== $withoutCharacter) {
                $builder->map(mb_ord($character), ['A' => $a, 'V' => $v][$character] ?? $empty);
            }
        }

        $builder->table('GPOS', OpenType::gpos(
            ['latn' => OpenType::script(OpenType::langSys([0]))],
            [['kern', [0]]],
            [OpenType::lookup(2, [OpenType::pairPosFormat1([$a => [$v => [-80, 0]], $v => [$a => [-60, 0]]])])],
        ));
        return $this->files[] = $builder->save();
    }
}
