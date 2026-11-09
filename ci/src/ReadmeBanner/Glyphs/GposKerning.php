<?php

namespace Webdevops\Build\ReadmeBanner\Glyphs;

use RuntimeException;

use function array_unique;
use function in_array;
use function sort;
use function sprintf;

/**
 * Applies the GPOS lookups of the features "kern" and "dist" to a pair of
 * glyphs, as a text shaper does with its default features, and returns how
 * much the advances change. Unlike a shaper it does not replace glyphs (GSUB)
 * first, so fonts that do that for the banner characters kern differently.
 */
final class GposKerning
{
    private const array FEATURES = ['kern', 'dist'];

    private const int SINGLE_ADJUSTMENT = 1;

    private const int PAIR_ADJUSTMENT = 2;

    private const int EXTENSION = 9;

    private const int IGNORE_BASE_GLYPHS = 0x0002;

    private const int IGNORE_LIGATURES = 0x0004;

    private const int IGNORE_MARKS = 0x0008;

    private const int USE_MARK_FILTERING_SET = 0x0010;

    private const int MARK_ATTACHMENT_TYPE = 0xFF00;

    private const int X_ADVANCE = 0x0004;

    /** @var array<string, list<array{type: int, flag: int, markSet: ?int, subtables: list<int>}>> lookups per preferred script tag */
    private array $lookups = [];

    /** @var array<int, array<int, int>> */
    private array $coverages = [];

    /** @var array<int, array<int, int>> */
    private array $classDefinitions = [];

    /** @var array<int, int> */
    private array $glyphClasses = [];

    /** @var array<int, int> */
    private array $markAttachmentClasses = [];

    /** @var list<int> offsets of the mark glyph set coverages in GDEF */
    private array $markSets = [];

    public function __construct(
        private readonly BinaryData $gpos,
        private readonly ?BinaryData $gdef,
        private readonly string $path,
    ) {
        if ($gdef !== null) {
            $glyphClassDef = $gdef->uint16(4);
            $this->glyphClasses = $glyphClassDef !== 0 ? $this->classDefinition($gdef, $glyphClassDef) : [];
            $markAttachClassDef = $gdef->uint16(10);
            $this->markAttachmentClasses = $markAttachClassDef !== 0 ? $this->classDefinition($gdef, $markAttachClassDef) : [];
            if ($gdef->uint32(0) >= 0x00010002 && $gdef->uint16(12) !== 0) {
                $sets = $gdef->uint16(12);
                for ($i = 0; $i < $gdef->uint16($sets + 2); $i++) {
                    $this->markSets[] = $sets + $gdef->uint32($sets + 4 + 4 * $i);
                }
            }
        }
    }

    /**
     * @param bool $latin whether the pair contains a Latin letter; other text uses the default script
     */
    public function adjustment(int $first, int $second, bool $latin): int
    {
        $advances = [0, 0];
        // Like a shaper, apply each lookup in turn; within a lookup the first subtable that matches wins
        foreach ($this->lookups($latin ? ['latn', 'DFLT', 'dflt'] : ['DFLT', 'dflt', 'latn']) as $lookup) {
            if ($lookup['type'] === self::SINGLE_ADJUSTMENT) {
                foreach ([$first, $second] as $i => $glyph) {
                    if ($this->isSkipped($glyph, $lookup)) {
                        continue;
                    }

                    foreach ($lookup['subtables'] as $offset) {
                        $value = $this->singleAdjustment($offset, $glyph);
                        if ($value !== null) {
                            $advances[$i] += $value;
                            break;
                        }
                    }
                }

                continue;
            }

            if ($this->isSkipped($first, $lookup) || $this->isSkipped($second, $lookup)) {
                continue;
            }

            foreach ($lookup['subtables'] as $offset) {
                $pair = $this->pairAdjustment($offset, $first, $second);
                if ($pair !== null) {
                    $advances[0] += $pair[0];
                    $advances[1] += $pair[1];
                    break;
                }
            }
        }

        return $advances[0] + $advances[1];
    }

    /**
     * The lookups of the kern features in the default language of the first script tag the font has.
     *
     * @param list<string> $scripts
     * @return list<array{type: int, flag: int, markSet: ?int, subtables: list<int>}>
     */
    private function lookups(array $scripts): array
    {
        $key = $scripts[0];
        if (isset($this->lookups[$key])) {
            return $this->lookups[$key];
        }

        $scriptList = $this->gpos->uint16(4);
        $featureList = $this->gpos->uint16(6);
        $lookupList = $this->gpos->uint16(8);

        $script = null;
        foreach ($scripts as $tag) {
            for ($i = 0; $i < $this->gpos->uint16($scriptList); $i++) {
                if ($this->gpos->tag($scriptList + 2 + 6 * $i) === $tag) {
                    $script = $scriptList + $this->gpos->uint16($scriptList + 2 + 6 * $i + 4);
                    break 2;
                }
            }
        }

        if ($script === null) {
            return $this->lookups[$key] = [];
        }

        $langSys = $this->gpos->uint16($script) !== 0 ? $script + $this->gpos->uint16($script) : null;
        for ($i = 0; $langSys === null && $i < $this->gpos->uint16($script + 2); $i++) {
            if ($this->gpos->tag($script + 4 + 6 * $i) === 'dflt') {
                $langSys = $script + $this->gpos->uint16($script + 4 + 6 * $i + 4);
            }
        }

        if ($langSys === null) {
            return $this->lookups[$key] = [];
        }

        $features = [];
        if ($this->gpos->uint16($langSys + 2) !== 0xFFFF) {
            $features[] = $this->gpos->uint16($langSys + 2);
        }

        for ($i = 0; $i < $this->gpos->uint16($langSys + 4); $i++) {
            $features[] = $this->gpos->uint16($langSys + 6 + 2 * $i);
        }

        $indices = [];
        foreach ($features as $feature) {
            $record = $featureList + 2 + 6 * $feature;
            if (!in_array($this->gpos->tag($record), self::FEATURES, true)) {
                continue;
            }

            $table = $featureList + $this->gpos->uint16($record + 4);
            for ($i = 0; $i < $this->gpos->uint16($table + 2); $i++) {
                $indices[] = $this->gpos->uint16($table + 4 + 2 * $i);
            }
        }

        $indices = array_unique($indices);
        sort($indices);

        $lookups = [];
        foreach ($indices as $index) {
            $lookup = $lookupList + $this->gpos->uint16($lookupList + 2 + 2 * $index);
            $type = $this->gpos->uint16($lookup);
            $flag = $this->gpos->uint16($lookup + 2);
            $count = $this->gpos->uint16($lookup + 4);
            $subtables = [];
            for ($i = 0; $i < $count; $i++) {
                $offset = $lookup + $this->gpos->uint16($lookup + 6 + 2 * $i);
                if ($this->gpos->uint16($lookup) === self::EXTENSION) {
                    $type = $this->gpos->uint16($offset + 2);
                    $offset += $this->gpos->uint32($offset + 4);
                }

                $subtables[] = $offset;
            }

            if ($type !== self::SINGLE_ADJUSTMENT && $type !== self::PAIR_ADJUSTMENT) {
                throw new RuntimeException(sprintf(
                    '%s kerns with GPOS lookups of type %d; only single (1) and pair adjustments (2) are supported.',
                    $this->path,
                    $type,
                ));
            }

            $markSet = ($flag & self::USE_MARK_FILTERING_SET) !== 0 ? $this->gpos->uint16($lookup + 6 + 2 * $count) : null;
            $lookups[] = ['type' => $type, 'flag' => $flag, 'markSet' => $markSet, 'subtables' => $subtables];
        }

        return $this->lookups[$key] = $lookups;
    }

    /**
     * Whether the lookup flag tells to leave the glyph out, based on its class in GDEF.
     *
     * @param array{flag: int, markSet: ?int} $lookup
     */
    private function isSkipped(int $glyph, array $lookup): bool
    {
        $flag = $lookup['flag'];
        return match ($this->glyphClasses[$glyph] ?? 0) {
            1 => ($flag & self::IGNORE_BASE_GLYPHS) !== 0,
            2 => ($flag & self::IGNORE_LIGATURES) !== 0,
            3 => ($flag & self::IGNORE_MARKS) !== 0
                || ($lookup['markSet'] !== null && $this->gdef instanceof BinaryData && !isset($this->coverage($this->gdef, $this->markSets[$lookup['markSet']] ?? -1)[$glyph]))
                || ($lookup['markSet'] === null && ($flag & self::MARK_ATTACHMENT_TYPE) !== 0
                    && ($flag & self::MARK_ATTACHMENT_TYPE) >> 8 !== ($this->markAttachmentClasses[$glyph] ?? 0)),
            default => false,
        };
    }

    private function singleAdjustment(int $offset, int $glyph): ?int
    {
        $index = $this->coverage($this->gpos, $offset + $this->gpos->uint16($offset + 2))[$glyph] ?? null;
        if ($index === null) {
            return null;
        }

        $format = $this->gpos->uint16($offset + 4);
        if ($this->gpos->uint16($offset) === 1) {
            return $this->xAdvance($offset + 6, $format);
        }

        return $this->xAdvance($offset + 8 + $index * $this->valueSize($format), $format);
    }

    /**
     * @return ?array{0: int, 1: int} advance change of both glyphs, or null if the subtable does not cover the pair
     */
    private function pairAdjustment(int $offset, int $first, int $second): ?array
    {
        $index = $this->coverage($this->gpos, $offset + $this->gpos->uint16($offset + 2))[$first] ?? null;
        if ($index === null) {
            return null;
        }

        $format1 = $this->gpos->uint16($offset + 4);
        $format2 = $this->gpos->uint16($offset + 6);
        $size1 = $this->valueSize($format1);
        $size2 = $this->valueSize($format2);

        if ($this->gpos->uint16($offset) === 1) {
            $pairSet = $offset + $this->gpos->uint16($offset + 10 + 2 * $index);
            $recordSize = 2 + $size1 + $size2;
            for ($i = 0; $i < $this->gpos->uint16($pairSet); $i++) {
                $record = $pairSet + 2 + $i * $recordSize;
                if ($this->gpos->uint16($record) === $second) {
                    return [$this->xAdvance($record + 2, $format1), $this->xAdvance($record + 2 + $size1, $format2)];
                }
            }

            return null;
        }

        $class1 = $this->classDefinition($this->gpos, $offset + $this->gpos->uint16($offset + 8))[$first] ?? 0;
        $class2 = $this->classDefinition($this->gpos, $offset + $this->gpos->uint16($offset + 10))[$second] ?? 0;
        $class1Count = $this->gpos->uint16($offset + 12);
        $class2Count = $this->gpos->uint16($offset + 14);
        if ($class1 >= $class1Count || $class2 >= $class2Count) {
            return null;
        }

        $record = $offset + 16 + ($class1 * $class2Count + $class2) * ($size1 + $size2);
        return [$this->xAdvance($record, $format1), $this->xAdvance($record + $size1, $format2)];
    }

    private function valueSize(int $format): int
    {
        $size = 0;
        for ($bit = 0; $bit < 8; $bit++) {
            $size += ($format >> $bit & 1) * 2;
        }

        return $size;
    }

    private function xAdvance(int $record, int $format): int
    {
        if (($format & self::X_ADVANCE) === 0) {
            return 0;
        }

        // XPlacement and YPlacement come first
        return $this->gpos->int16($record + $this->valueSize($format & 0x3));
    }

    /**
     * @return array<int, int> coverage index per glyph
     */
    private function coverage(BinaryData $table, int $offset): array
    {
        if ($offset < 0) {
            return [];
        }

        $key = $offset * 2 + ($table === $this->gpos ? 0 : 1);
        if (isset($this->coverages[$key])) {
            return $this->coverages[$key];
        }

        $map = [];
        $count = $table->uint16($offset + 2);
        if ($table->uint16($offset) === 1) {
            for ($i = 0; $i < $count; $i++) {
                $map[$table->uint16($offset + 4 + 2 * $i)] = $i;
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $range = $offset + 4 + 6 * $i;
                $startIndex = $table->uint16($range + 4);
                for ($glyph = $table->uint16($range); $glyph <= $table->uint16($range + 2); $glyph++) {
                    $map[$glyph] = $startIndex + $glyph - $table->uint16($range);
                }
            }
        }

        return $this->coverages[$key] = $map;
    }

    /**
     * @return array<int, int> class per glyph; glyphs that are not listed have class 0
     */
    private function classDefinition(BinaryData $table, int $offset): array
    {
        $key = $offset * 2 + ($table === $this->gpos ? 0 : 1);
        if (isset($this->classDefinitions[$key])) {
            return $this->classDefinitions[$key];
        }

        $map = [];
        if ($table->uint16($offset) === 1) {
            $start = $table->uint16($offset + 2);
            for ($i = 0; $i < $table->uint16($offset + 4); $i++) {
                $map[$start + $i] = $table->uint16($offset + 6 + 2 * $i);
            }
        } else {
            for ($i = 0; $i < $table->uint16($offset + 2); $i++) {
                $range = $offset + 4 + 6 * $i;
                for ($glyph = $table->uint16($range); $glyph <= $table->uint16($range + 2); $glyph++) {
                    $map[$glyph] = $table->uint16($range + 4);
                }
            }
        }

        return $this->classDefinitions[$key] = $map;
    }
}
