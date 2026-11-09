<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\Glyphs\TrueTypeFont;

use function array_map;
use function str_repeat;
use function unlink;

#[CoversClass(TrueTypeFont::class)]
final class TrueTypeFontTest extends TestCase
{
    private const array SQUARE = [[[100, 0], [500, 0], [500, 700], [100, 700]]];

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testReadsTheHeader(): void
    {
        $path = $this->save(new FontBuilder(2048));
        $font = new TrueTypeFont($path);

        self::assertSame(2048, $font->unitsPerEm());
        self::assertSame($path, $font->path());
    }

    /**
     * @return iterable<string, array{0: int}>
     */
    public static function cmapFormats(): iterable
    {
        yield 'format 4 with deltas' => [FontBuilder::CMAP_FORMAT_4];
        yield 'format 4 with a glyph array' => [FontBuilder::CMAP_FORMAT_4_GLYPH_ARRAY];
        yield 'format 12' => [FontBuilder::CMAP_FORMAT_12];
    }

    #[DataProvider('cmapFormats')]
    public function testMapsCharactersToGlyphs(int $format): void
    {
        $builder = new FontBuilder();
        $a = $builder->simpleGlyph(600, self::SQUARE);
        $b = $builder->emptyGlyph(300);
        $builder->map(0x41, $a)->map(0x2026, $b)->map(0x43, 99)->cmapFormat($format);
        $font = new TrueTypeFont($this->save($builder));

        self::assertSame($a, $font->glyphId(0x41));
        self::assertSame($b, $font->glyphId(0x2026));
        self::assertNull($font->glyphId(0x42), 'B is not mapped');
        self::assertNull($font->glyphId(0x43), 'C is mapped to a glyph the font does not have');
    }

    public function testMapsCodePointsAboveTheBasicPlane(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->emptyGlyph(1000);
        $builder->map(0x1F600, $glyph)->cmapFormat(FontBuilder::CMAP_FORMAT_12);

        self::assertSame($glyph, new TrueTypeFont($this->save($builder))->glyphId(0x1F600));
    }

    public function testPrefersTheFullUnicodeCmap(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->emptyGlyph(1000);
        $format12 = OpenType::uint16(12, 0) . OpenType::uint32(28, 0, 1, 0x41, 0x41, $glyph);
        $format4 = OpenType::uint16(4, 32, 0, 4, 0, 0, 0, 0x41, 0xFFFF, 0, 0x41, 0xFFFF, -0x41, 1, 0, 0);
        $cmap = OpenType::uint16(0, 2, 3, 1) . OpenType::uint32(20) . OpenType::uint16(3, 10) . OpenType::uint32(20 + 32) . $format4 . $format12;
        $builder->table('cmap', $cmap);

        self::assertSame($glyph, new TrueTypeFont($this->save($builder))->glyphId(0x41), 'format 4 maps A to glyph 0, format 12 to the glyph');
    }

    public function testAdvanceWidthOfGlyphsAfterTheLastMetricIsTheLastAdvance(): void
    {
        $builder = new FontBuilder();
        $first = $builder->emptyGlyph(600);
        $second = $builder->emptyGlyph(700);
        $builder->numberOfHMetrics(2);
        $font = new TrueTypeFont($this->save($builder));

        self::assertSame(500, $font->advanceWidth(0));
        self::assertSame(600, $font->advanceWidth($first));
        self::assertSame(600, $font->advanceWidth($second));
    }

    /**
     * @return iterable<string, array{0: list<list<array{0: int, 1: int, 2?: bool}>>, 1: string}>
     */
    public static function outlines(): iterable
    {
        yield 'lines' => [self::SQUARE, 'M 100 0 L 500 0 L 500 700 L 100 700 Z'];
        yield 'quadratic curve' => [[[[0, 0], [100, 200, false], [200, 0]]], 'M 0 0 Q 100 200 200 0 Z'];
        yield 'curve with an implied on-curve point' => [
            [[[0, 0], [0, 100, false], [100, 100, false], [100, 0]]],
            'M 0 0 Q 0 100 50 100 Q 100 100 100 0 Z',
        ];
        yield 'contour that starts off the curve' => [
            [[[0, 100, false], [100, 100], [100, 0], [0, 0]]],
            'M 100 100 L 100 0 L 0 0 Q 0 100 100 100 Z',
        ];
        yield 'contour without on-curve points' => [
            [[[0, 0, false], [100, 0, false], [100, 100, false], [0, 100, false]]],
            'M 0 50 Q 0 0 50 0 Q 100 0 100 50 Q 100 100 50 100 Q 0 100 0 50 Z',
        ];
        yield 'two contours, large and repeated coordinates' => [
            [[[0, 0], [1000, 0], [1000, -900]], [[1000, -900], [1000, -900], [-300, -900]]],
            'M 0 0 L 1000 0 L 1000 -900 Z M 1000 -900 L 1000 -900 L -300 -900 Z',
        ];
    }

    /**
     * @param list<list<array{0: int, 1: int, 2?: bool}>> $contours
     */
    #[DataProvider('outlines')]
    public function testOutlineStartsEachContourAtItsLastOnCurvePoint(array $contours, string $expected): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->simpleGlyph(600, $contours);

        self::assertSame($expected, new TrueTypeFont($this->save($builder))->outline($glyph));
    }

    public function testOutlineWithLongLoca(): void
    {
        $builder = new FontBuilder();
        $builder->simpleGlyph(600, [[[0, 0], [10, 10], [20, 0]]]);

        $glyph = $builder->simpleGlyph(600, self::SQUARE);
        $builder->longLoca();

        self::assertSame('M 100 0 L 500 0 L 500 700 L 100 700 Z', new TrueTypeFont($this->save($builder))->outline($glyph));
    }

    public function testEmptyGlyphHasNoOutline(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->emptyGlyph(250);

        self::assertSame('', new TrueTypeFont($this->save($builder))->outline($glyph));
    }

    public function testLeftSideBearingMovesTheGlyph(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->simpleGlyph(600, self::SQUARE, 150);
        $builder->numberOfHMetrics(1);

        self::assertSame('M 150 0 L 550 0 L 550 700 L 150 700 Z', new TrueTypeFont($this->save($builder))->outline($glyph));
    }

    /**
     * @return iterable<string, array{0: list<array{dx: int, dy: int, scale?: float|list<float>}>, 1: string}>
     */
    public static function components(): iterable
    {
        yield 'moved' => [[['dx' => 10, 'dy' => -5]], 'M 110 -5 L 510 -5 L 510 695 L 110 695 Z'];
        yield 'moved far and scaled' => [[['dx' => 1000, 'dy' => -10, 'scale' => 0.5]], 'M 1050 -10 L 1250 -10 L 1250 340 L 1050 340 Z'];
        yield 'x and y scale' => [[['dx' => 0, 'dy' => 0, 'scale' => [1.5, 0.5]]], 'M 150 0 L 750 0 L 750 350 L 150 350 Z'];
        yield '2x2 matrix' => [[['dx' => 0, 'dy' => 0, 'scale' => [1.0, 0.0, 0.5, 1.0]]], 'M 100 0 L 500 0 L 850 700 L 450 700 Z'];
        yield 'two components' => [
            [['dx' => 0, 'dy' => 0], ['dx' => 600, 'dy' => 0]],
            'M 100 0 L 500 0 L 500 700 L 100 700 Z M 700 0 L 1100 0 L 1100 700 L 700 700 Z',
        ];
    }

    /**
     * @param list<array{dx: int, dy: int, scale?: float|list<float>}> $components
     */
    #[DataProvider('components')]
    public function testCompositeGlyphDrawsItsComponents(array $components, string $expected): void
    {
        $builder = new FontBuilder();
        // The left side bearing of a component does not move it
        $square = $builder->simpleGlyph(600, self::SQUARE, 300);
        $glyph = $builder->compositeGlyph(600, array_map(fn (array $component): array => ['glyph' => $square, ...$component], $components));

        self::assertSame($expected, new TrueTypeFont($this->save($builder))->outline($glyph));
    }

    public function testNestedCompositeGlyphCombinesTheTransformations(): void
    {
        $builder = new FontBuilder();
        $square = $builder->simpleGlyph(600, self::SQUARE);
        $inner = $builder->compositeGlyph(600, [['glyph' => $square, 'dx' => 10, 'dy' => 0, 'scale' => 0.5]]);
        $outer = $builder->compositeGlyph(600, [['glyph' => $inner, 'dx' => 100, 'dy' => 0, 'scale' => 1.5]]);

        self::assertSame('M 190 0 L 490 0 L 490 525 L 190 525 Z', new TrueTypeFont($this->save($builder))->outline($outer));
    }

    public function testKerningIsZeroWithoutGpos(): void
    {
        $builder = new FontBuilder();
        $a = $builder->emptyGlyph(600);

        self::assertSame(0, new TrueTypeFont($this->save($builder))->kerning($a, $a, true));
    }

    public function testKerningComesFromGpos(): void
    {
        $builder = new FontBuilder();
        $a = $builder->emptyGlyph(600);
        $v = $builder->emptyGlyph(600);
        $builder->table('GPOS', $this->gpos($a, $v));
        $font = new TrueTypeFont($this->save($builder));

        self::assertSame(-80, $font->kerning($a, $v, true));
        self::assertSame(0, $font->kerning($v, $a, true));
    }

    public function testKerningUsesTheGlyphClassesOfGdef(): void
    {
        $builder = new FontBuilder();
        $a = $builder->emptyGlyph(600);
        $v = $builder->emptyGlyph(600);
        $builder->table('GPOS', $this->gpos($a, $v, 0x0008));
        $builder->table('GDEF', OpenType::gdef(OpenType::classDefFormat1($v, 3)));

        self::assertSame(0, new TrueTypeFont($this->save($builder))->kerning($a, $v, true), 'the lookup ignores marks and GDEF makes V a mark');
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The font file /does/not/exist.ttf does not exist.');
        new TrueTypeFont('/does/not/exist.ttf');
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function otherFormats(): iterable
    {
        yield 'CFF outlines' => ['OTTO', 'has PostScript (CFF) outlines. Use the .ttf file of the font.'];
        yield 'WOFF' => ['wOFF', 'is a compressed web font. Use the .ttf file of the font.'];
        yield 'WOFF2' => ['wOF2', 'is a compressed web font. Use the .ttf file of the font.'];
        yield 'something else' => ['%PDF', 'is not a TrueType font (.ttf).'];
    }

    #[DataProvider('otherFormats')]
    public function testOtherFileFormatsAreRejected(string $version, string $message): void
    {
        $path = $this->save(new FontBuilder()->version($version));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' ' . $message);
        new TrueTypeFont($path);
    }

    public function testMacTrueTypeVersionIsAccepted(): void
    {
        self::assertSame(1000, new TrueTypeFont($this->save(new FontBuilder()->version('true')))->unitsPerEm());
    }

    public function testMissingTableIsRejected(): void
    {
        $path = $this->save(new FontBuilder()->table('hhea', null));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' has no "hhea" table.');
        new TrueTypeFont($path);
    }

    /**
     * @return iterable<string, array{0: int, 1: int, 2: string, 3: string}>
     */
    public static function unsupportedCmaps(): iterable
    {
        yield 'Macintosh only' => [1, 0, OpenType::uint16(0, 262, 0) . str_repeat("\0", 256), 'has no Unicode character map.'];
        yield 'format 6' => [3, 1, OpenType::uint16(6, 10, 0, 0, 0), 'maps Unicode with a cmap subtable of format 6; only the formats 4 and 12 are supported.'];
    }

    #[DataProvider('unsupportedCmaps')]
    public function testUnsupportedCmapIsRejected(int $platform, int $encoding, string $subtable, string $message): void
    {
        $path = $this->save(new FontBuilder()->rawCmap($platform, $encoding, $subtable));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' ' . $message);
        new TrueTypeFont($path);
    }

    public function testCubicOutlinesAreRejected(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->simpleGlyph(600, [[[0, 0], [50, 100, 'cubic'], [100, 0]]]);
        $path = $this->save($builder);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' has cubic TrueType outlines, which are not supported.');
        new TrueTypeFont($path)->outline($glyph);
    }

    public function testComponentPositionedByPointsIsRejected(): void
    {
        $builder = new FontBuilder();
        $square = $builder->simpleGlyph(600, self::SQUARE);
        $glyph = $builder->compositeGlyph(600, [['glyph' => $square, 'dx' => 0, 'dy' => 0, 'matchPoints' => true]]);
        $path = $this->save($builder);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' positions a component of a glyph by matching points, which is not supported.');
        new TrueTypeFont($path)->outline($glyph);
    }

    public function testGlyphOutsideTheFontIsRejected(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->compositeGlyph(600, [['glyph' => 42, 'dx' => 0, 'dy' => 0]]);
        $path = $this->save($builder);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' refers to glyph 42, but has only 2 glyphs.');
        new TrueTypeFont($path)->outline($glyph);
    }

    public function testEndlessComponentNestingIsRejected(): void
    {
        $builder = new FontBuilder();
        $glyph = $builder->compositeGlyph(600, [['glyph' => 1, 'dx' => 0, 'dy' => 0]]);
        $path = $this->save($builder);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($path . ' has components nested too deep in glyph ' . $glyph . '.');
        new TrueTypeFont($path)->outline($glyph);
    }

    /**
     * The feature "kern" of the Latin script moves $second 80 units closer to $first.
     */
    private function gpos(int $first, int $second, int $lookupFlag = 0): string
    {
        return OpenType::gpos(
            ['latn' => OpenType::script(OpenType::langSys([0]))],
            [['kern', [0]]],
            [OpenType::lookup(2, [OpenType::pairPosFormat1([$first => [$second => [-80, 0]]])], $lookupFlag)],
        );
    }

    private function save(FontBuilder $builder): string
    {
        return $this->files[] = $builder->save();
    }
}
