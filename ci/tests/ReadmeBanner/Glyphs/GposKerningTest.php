<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\Glyphs\BinaryData;
use Webdevops\Build\ReadmeBanner\Glyphs\GposKerning;

#[CoversClass(GposKerning::class)]
final class GposKerningTest extends TestCase
{
    private const int A = 1;

    private const int B = 2;

    private const int V = 3;

    private const int SINGLE = 1;

    private const int PAIR = 2;

    private const int IGNORE_BASE_GLYPHS = 0x0002;

    private const int IGNORE_LIGATURES = 0x0004;

    private const int IGNORE_MARKS = 0x0008;

    private const int USE_MARK_FILTERING_SET = 0x0010;

    public function testPairAdjustmentByGlyphList(): void
    {
        $kerning = $this->kerning([OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-80, 0]]])])]);

        self::assertSame(-80, $kerning->adjustment(self::A, self::V, true));
        self::assertSame(0, $kerning->adjustment(self::V, self::A, true));
        self::assertSame(0, $kerning->adjustment(self::A, self::B, true));
    }

    public function testOnlyXAdvanceCountsForBothGlyphs(): void
    {
        $format1 = OpenType::X_PLACEMENT | OpenType::Y_PLACEMENT | OpenType::X_ADVANCE | OpenType::Y_ADVANCE;
        $pairPos = OpenType::pairPosFormat1([self::A => [self::V => [-80, 30]]], $format1, OpenType::X_PLACEMENT | OpenType::X_ADVANCE);

        self::assertSame(-50, $this->kerning([OpenType::lookup(self::PAIR, [$pairPos])])->adjustment(self::A, self::V, true));
    }

    public function testValueRecordWithoutXAdvanceChangesNothing(): void
    {
        $pairPos = OpenType::pairPosFormat1([self::A => [self::V => [0, 0]]], OpenType::X_PLACEMENT, OpenType::Y_ADVANCE);

        self::assertSame(0, $this->kerning([OpenType::lookup(self::PAIR, [$pairPos])])->adjustment(self::A, self::V, true));
    }

    public function testPairAdjustmentByGlyphClass(): void
    {
        $pairPos = OpenType::pairPosFormat2(
            OpenType::coverageFormat2([self::A, self::B]),
            OpenType::classDefFormat2([self::A, self::B, 1]),
            OpenType::classDefFormat1(self::V, 1),
            [[[0, 0], [0, 0]], [[0, 0], [-50, -5]]],
            OpenType::X_ADVANCE,
            OpenType::X_ADVANCE,
        );
        $kerning = $this->kerning([OpenType::lookup(self::PAIR, [$pairPos])]);

        self::assertSame(-55, $kerning->adjustment(self::A, self::V, true));
        self::assertSame(-55, $kerning->adjustment(self::B, self::V, true));
        self::assertSame(0, $kerning->adjustment(self::A, self::B, true), 'B has class 0 as second glyph');
    }

    public function testClassOutsideTheClassCountsChangesNothing(): void
    {
        $pairPos = OpenType::pairPosFormat2(
            OpenType::coverageFormat1(self::A),
            OpenType::classDefFormat1(self::A, 5),
            OpenType::classDefFormat1(self::V, 1),
            [[[0, 0], [-50, 0]], [[0, 0], [-50, 0]]],
        );

        self::assertSame(0, $this->kerning([OpenType::lookup(self::PAIR, [$pairPos])])->adjustment(self::A, self::V, true));
    }

    public function testSingleAdjustmentAppliesToEachGlyph(): void
    {
        $kerning = $this->kerning([OpenType::lookup(self::SINGLE, [OpenType::singlePosFormat1(OpenType::coverageFormat1(self::A, self::V), 20)])]);

        self::assertSame(40, $kerning->adjustment(self::A, self::V, true));
        self::assertSame(20, $kerning->adjustment(self::B, self::V, true));
        self::assertSame(0, $kerning->adjustment(self::B, self::B, true));
    }

    public function testSingleAdjustmentWithAValuePerGlyph(): void
    {
        $singlePos = OpenType::singlePosFormat2(
            OpenType::coverageFormat1(self::A, self::V),
            [7, -3],
            OpenType::X_PLACEMENT | OpenType::X_ADVANCE,
        );

        self::assertSame(4, $this->kerning([OpenType::lookup(self::SINGLE, [$singlePos])])->adjustment(self::A, self::V, true));
    }

    public function testFirstMatchingSubtableOfALookupWins(): void
    {
        $lookup = OpenType::lookup(self::PAIR, [
            OpenType::pairPosFormat1([self::B => [self::V => [-90, 0]]]),
            OpenType::pairPosFormat1([self::A => [self::V => [-30, 0]]]),
            OpenType::pairPosFormat1([self::A => [self::V => [-60, 0]]]),
        ]);

        self::assertSame(-30, $this->kerning([$lookup])->adjustment(self::A, self::V, true));
    }

    public function testEveryLookupOfTheFeatureApplies(): void
    {
        $kerning = $this->kerning([
            OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-30, 0]]])]),
            OpenType::lookup(self::SINGLE, [OpenType::singlePosFormat1(OpenType::coverageFormat1(self::V), 5)]),
        ]);

        self::assertSame(-25, $kerning->adjustment(self::A, self::V, true));
    }

    public function testExtensionLookupPointsToTheRealSubtable(): void
    {
        $lookup = OpenType::lookup(9, [OpenType::extension(self::PAIR, OpenType::pairPosFormat1([self::A => [self::V => [-70, 0]]]))]);

        self::assertSame(-70, $this->kerning([$lookup])->adjustment(self::A, self::V, true));
    }

    public function testOtherLookupTypesAreRejected(): void
    {
        $kerning = $this->kerning([OpenType::lookup(4, [OpenType::coverageFormat1(self::A)])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test.ttf kerns with GPOS lookups of type 4; only single (1) and pair adjustments (2) are supported.');
        $kerning->adjustment(self::A, self::V, true);
    }

    public function testOnlyTheKernAndDistFeaturesApply(): void
    {
        $gpos = OpenType::gpos(
            ['DFLT' => OpenType::script(OpenType::langSys([0, 1, 2]))],
            [['kern', [0]], ['liga', [1]], ['dist', [2]]],
            [
                OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-10, 0]]])]),
                OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-200, 0]]])]),
                OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-3, 0]]])]),
            ],
        );

        self::assertSame(-13, new GposKerning(new BinaryData($gpos), null, 'test.ttf')->adjustment(self::A, self::V, false));
    }

    public function testRequiredFeatureApplies(): void
    {
        $gpos = OpenType::gpos(
            ['DFLT' => OpenType::script(OpenType::langSys([], 0))],
            [['kern', [0]]],
            [OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-10, 0]]])])],
        );

        self::assertSame(-10, new GposKerning(new BinaryData($gpos), null, 'test.ttf')->adjustment(self::A, self::V, false));
    }

    /**
     * @return iterable<string, array{0: array<string, string>, 1: bool, 2: int}>
     */
    public static function scripts(): iterable
    {
        // Feature 0 has the lookup with -80, feature 1 the one with -10
        $latinLangSys = OpenType::langSys([0]);
        $defaultLangSys = OpenType::langSys([1]);
        $latin = OpenType::script($latinLangSys);
        $default = OpenType::script($defaultLangSys);
        yield 'Latin pair uses latn' => [['DFLT' => $default, 'latn' => $latin], true, -80];
        yield 'other pair uses DFLT' => [['DFLT' => $default, 'latn' => $latin], false, -10];
        yield 'Latin pair falls back to DFLT' => [['DFLT' => $default], true, -10];
        yield 'other pair falls back to latn' => [['latn' => $latin], false, -80];
        yield 'dflt is a default script too' => [['dflt' => $default, 'cyrl' => $latin], false, -10];
        yield 'no matching script' => [['cyrl' => $latin], true, 0];
        yield 'language system named dflt' => [['latn' => OpenType::script(null, ['DEU ' => $defaultLangSys, 'dflt' => $latinLangSys])], true, -80];
        yield 'no default language system' => [['latn' => OpenType::script(null, ['DEU ' => $latinLangSys])], true, 0];
    }

    /**
     * @param array<string, string> $scripts
     */
    #[DataProvider('scripts')]
    public function testScriptOfThePair(array $scripts, bool $latin, int $expected): void
    {
        $gpos = OpenType::gpos(
            $scripts,
            [['kern', [0]], ['kern', [1]]],
            [
                OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-80, 0]]])]),
                OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-10, 0]]])]),
            ],
        );
        $kerning = new GposKerning(new BinaryData($gpos), null, 'test.ttf');

        self::assertSame($expected, $kerning->adjustment(self::A, self::V, $latin));
        self::assertSame($expected, $kerning->adjustment(self::A, self::V, $latin), 'the second call reads the cached lookups');
    }

    /**
     * @return iterable<string, array{0: int, 1: ?int, 2: string, 3: int}>
     */
    public static function lookupFlags(): iterable
    {
        // V is a mark (class 3) with mark attachment class 2, A a base glyph (class 1), B a ligature (class 2)
        yield 'no flag' => [0, null, 'AV', -80];
        yield 'ignore marks' => [self::IGNORE_MARKS, null, 'AV', 0];
        yield 'ignore marks keeps base glyphs' => [self::IGNORE_MARKS, null, 'AB', -40];
        yield 'ignore base glyphs' => [self::IGNORE_BASE_GLYPHS, null, 'AV', 0];
        yield 'ignore ligatures' => [self::IGNORE_LIGATURES, null, 'AB', 0];
        yield 'ignore ligatures keeps marks' => [self::IGNORE_LIGATURES, null, 'AV', -80];
        yield 'mark in the filtering set' => [self::USE_MARK_FILTERING_SET, 0, 'AV', -80];
        yield 'mark not in the filtering set' => [self::USE_MARK_FILTERING_SET, 1, 'AV', 0];
        yield 'mark filtering set that does not exist' => [self::USE_MARK_FILTERING_SET, 7, 'AV', 0];
        yield 'mark of the attachment type' => [0x0200, null, 'AV', -80];
        yield 'mark of another attachment type' => [0x0100, null, 'AV', 0];
    }

    #[DataProvider('lookupFlags')]
    public function testLookupFlagLeavesOutGlyphs(int $flag, ?int $markSet, string $pair, int $expected): void
    {
        $glyphs = ['A' => self::A, 'B' => self::B, 'V' => self::V];
        $gdef = OpenType::gdef(
            OpenType::classDefFormat1(self::A, 1, 2, 3),
            OpenType::classDefFormat1(self::V, 2),
            [OpenType::coverageFormat1(self::V), OpenType::coverageFormat1(self::B)],
        );
        $lookup = OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-80, 0], self::B => [-40, 0]]])], $flag, $markSet);

        self::assertSame($expected, $this->kerning([$lookup], $gdef)->adjustment($glyphs[$pair[0]], $glyphs[$pair[1]], true));
    }

    public function testSingleAdjustmentLeavesOutIgnoredGlyphs(): void
    {
        $gdef = OpenType::gdef(OpenType::classDefFormat2([self::V, self::V, 3]));
        $lookup = OpenType::lookup(self::SINGLE, [OpenType::singlePosFormat1(OpenType::coverageFormat1(self::A, self::V), 20)], self::IGNORE_MARKS);

        self::assertSame(20, $this->kerning([$lookup], $gdef)->adjustment(self::A, self::V, true));
    }

    public function testGdefWithoutClassDefinitions(): void
    {
        $lookup = OpenType::lookup(self::PAIR, [OpenType::pairPosFormat1([self::A => [self::V => [-80, 0]]])], self::IGNORE_MARKS);

        self::assertSame(-80, $this->kerning([$lookup], OpenType::gdef(null))->adjustment(self::A, self::V, true));
    }

    /**
     * A GPOS table with the lookups in the feature "kern" of the default script.
     *
     * @param list<string> $lookups
     */
    private function kerning(array $lookups, ?string $gdef = null): GposKerning
    {
        $gpos = OpenType::gpos(['DFLT' => OpenType::script(OpenType::langSys([0]))], [['kern', array_keys($lookups)]], $lookups);
        return new GposKerning(new BinaryData($gpos), $gdef === null ? null : new BinaryData($gdef), 'test.ttf');
    }
}
