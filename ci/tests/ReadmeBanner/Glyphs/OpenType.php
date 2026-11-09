<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use function array_map;
use function chr;
use function count;
use function is_string;
use function ksort;
use function pack;
use function strlen;

/**
 * Writes the binary OpenType structures the font tests need, big-endian like the font files.
 *
 * A table is a list of parts: bytes, or an offset to a subtable. The subtables are placed
 * after the last part, in order, and each offset points to its subtable from the start of
 * the table, as OpenType offsets do.
 */
final class OpenType
{
    public const int X_PLACEMENT = 0x0001;

    public const int Y_PLACEMENT = 0x0002;

    public const int X_ADVANCE = 0x0004;

    public const int Y_ADVANCE = 0x0008;

    /**
     * Value of the value record fields other than XAdvance, so a wrong field gives a wrong result.
     */
    public const int DECOY = 111;

    public static function uint16(int ...$values): string
    {
        $bytes = '';
        foreach ($values as $value) {
            $bytes .= pack('n', $value & 0xFFFF);
        }

        return $bytes;
    }

    public static function int16(int ...$values): string
    {
        return self::uint16(...$values);
    }

    public static function int8(int ...$values): string
    {
        $bytes = '';
        foreach ($values as $value) {
            $bytes .= chr($value & 0xFF);
        }

        return $bytes;
    }

    public static function uint32(int ...$values): string
    {
        $bytes = '';
        foreach ($values as $value) {
            $bytes .= pack('N', $value);
        }

        return $bytes;
    }

    /**
     * @return array{0: 16, 1: ?string} an Offset16 to $table, 0 for null
     */
    public static function offset16(?string $table): array
    {
        return [16, $table];
    }

    /**
     * @return array{0: 32, 1: ?string} an Offset32 to $table, 0 for null
     */
    public static function offset32(?string $table): array
    {
        return [32, $table];
    }

    /**
     * @param string|array{0: 16|32, 1: ?string} ...$parts
     */
    public static function table(string|array ...$parts): string
    {
        $headerLength = 0;
        foreach ($parts as $part) {
            $headerLength += is_string($part) ? strlen($part) : $part[0] / 8;
        }

        $header = '';
        $subtables = '';
        foreach ($parts as $part) {
            if (is_string($part)) {
                $header .= $part;
                continue;
            }

            [$size, $table] = $part;
            $offset = $table === null ? 0 : $headerLength + strlen($subtables);
            $header .= $size === 16 ? self::uint16($offset) : self::uint32($offset);
            $subtables .= $table ?? '';
        }

        return $header . $subtables;
    }

    /**
     * @param array<string, string> $scripts script table per script tag
     * @param list<array{0: string, 1: list<int>}> $features tag and lookup indices of each feature
     * @param list<string> $lookups
     */
    public static function gpos(array $scripts, array $features, array $lookups): string
    {
        $scriptList = [self::uint16(count($scripts))];
        foreach ($scripts as $tag => $script) {
            $scriptList[] = $tag;
            $scriptList[] = self::offset16($script);
        }

        $featureList = [self::uint16(count($features))];
        foreach ($features as [$tag, $lookupIndices]) {
            $featureList[] = $tag;
            $featureList[] = self::offset16(self::uint16(0, count($lookupIndices), ...$lookupIndices));
        }

        return self::table(
            self::uint16(1, 0),
            self::offset16(self::table(...$scriptList)),
            self::offset16(self::table(...$featureList)),
            self::offset16(self::table(self::uint16(count($lookups)), ...array_map(self::offset16(...), $lookups))),
        );
    }

    /**
     * @param array<string, string> $langSystems language system per language tag
     */
    public static function script(?string $defaultLangSys, array $langSystems = []): string
    {
        $parts = [self::offset16($defaultLangSys), self::uint16(count($langSystems))];
        foreach ($langSystems as $tag => $langSys) {
            $parts[] = $tag;
            $parts[] = self::offset16($langSys);
        }

        return self::table(...$parts);
    }

    /**
     * @param list<int> $featureIndices
     */
    public static function langSys(array $featureIndices, int $requiredFeature = 0xFFFF): string
    {
        return self::uint16(0, $requiredFeature, count($featureIndices), ...$featureIndices);
    }

    /**
     * @param list<string> $subtables
     */
    public static function lookup(int $type, array $subtables, int $flag = 0, ?int $markSet = null): string
    {
        return self::table(
            self::uint16($type, $flag, count($subtables)),
            ...[...array_map(self::offset16(...), $subtables), $markSet === null ? '' : self::uint16($markSet)],
        );
    }

    /**
     * Pair adjustment with a list of second glyphs per first glyph.
     *
     * @param array<int, array<int, array{0: int, 1: int}>> $pairs XAdvance of the first and the second glyph per pair
     */
    public static function pairPosFormat1(array $pairs, int $format1 = self::X_ADVANCE, int $format2 = 0): string
    {
        ksort($pairs);
        $parts = [
            self::uint16(1),
            self::offset16(self::coverageFormat1(...array_keys($pairs))),
            self::uint16($format1, $format2, count($pairs)),
        ];
        foreach ($pairs as $seconds) {
            ksort($seconds);
            $pairSet = self::uint16(count($seconds));
            foreach ($seconds as $second => [$first, $other]) {
                $pairSet .= self::uint16($second) . self::valueRecord($format1, $first) . self::valueRecord($format2, $other);
            }

            $parts[] = self::offset16($pairSet);
        }

        return self::table(...$parts);
    }

    /**
     * Pair adjustment by glyph classes.
     *
     * @param list<list<array{0: int, 1: int}>> $values XAdvance of the first and the second glyph per first and second class
     */
    public static function pairPosFormat2(string $coverage, string $classDef1, string $classDef2, array $values, int $format1 = self::X_ADVANCE, int $format2 = 0): string
    {
        $records = '';
        foreach ($values as $row) {
            foreach ($row as [$first, $second]) {
                $records .= self::valueRecord($format1, $first) . self::valueRecord($format2, $second);
            }
        }

        return self::table(
            self::uint16(2),
            self::offset16($coverage),
            self::uint16($format1, $format2),
            self::offset16($classDef1),
            self::offset16($classDef2),
            self::uint16(count($values), count($values[0] ?? [])),
            $records,
        );
    }

    /**
     * Single adjustment with the same value for every covered glyph.
     */
    public static function singlePosFormat1(string $coverage, int $xAdvance, int $format = self::X_ADVANCE): string
    {
        return self::table(self::uint16(1), self::offset16($coverage), self::uint16($format), self::valueRecord($format, $xAdvance));
    }

    /**
     * Single adjustment with a value per covered glyph, in coverage order.
     *
     * @param list<int> $xAdvances
     */
    public static function singlePosFormat2(string $coverage, array $xAdvances, int $format = self::X_ADVANCE): string
    {
        $records = '';
        foreach ($xAdvances as $xAdvance) {
            $records .= self::valueRecord($format, $xAdvance);
        }

        return self::table(self::uint16(2), self::offset16($coverage), self::uint16($format, count($xAdvances)), $records);
    }

    public static function extension(int $type, string $subtable): string
    {
        return self::table(self::uint16(1, $type), self::offset32($subtable));
    }

    public static function coverageFormat1(int ...$glyphs): string
    {
        return self::uint16(1, count($glyphs), ...$glyphs);
    }

    /**
     * @param array{0: int, 1: int} ...$ranges first and last glyph of each range
     */
    public static function coverageFormat2(array ...$ranges): string
    {
        $bytes = self::uint16(2, count($ranges));
        $index = 0;
        foreach ($ranges as [$start, $end]) {
            $bytes .= self::uint16($start, $end, $index);
            $index += $end - $start + 1;
        }

        return $bytes;
    }

    public static function classDefFormat1(int $startGlyph, int ...$classes): string
    {
        return self::uint16(1, $startGlyph, count($classes), ...$classes);
    }

    /**
     * @param array{0: int, 1: int, 2: int} ...$ranges first glyph, last glyph and class of each range
     */
    public static function classDefFormat2(array ...$ranges): string
    {
        $bytes = self::uint16(2, count($ranges));
        foreach ($ranges as [$start, $end, $class]) {
            $bytes .= self::uint16($start, $end, $class);
        }

        return $bytes;
    }

    /**
     * The fields of $format in their order; XAdvance gets $xAdvance, the others the decoy.
     */
    public static function valueRecord(int $format, int $xAdvance): string
    {
        $bytes = '';
        foreach ([self::X_PLACEMENT, self::Y_PLACEMENT, self::X_ADVANCE, self::Y_ADVANCE] as $field) {
            if (($format & $field) !== 0) {
                $bytes .= self::int16($field === self::X_ADVANCE ? $xAdvance : self::DECOY);
            }
        }

        return $bytes;
    }

    /**
     * @param list<string> $markSets coverage of each mark glyph set; any set makes it GDEF 1.2
     */
    public static function gdef(?string $glyphClassDef, ?string $markAttachClassDef = null, array $markSets = []): string
    {
        $parts = [
            self::uint16(1, $markSets === [] ? 0 : 2),
            self::offset16($glyphClassDef),
            self::offset16(null),
            self::offset16(null),
            self::offset16($markAttachClassDef),
        ];
        if ($markSets !== []) {
            $parts[] = self::offset16(self::table(self::uint16(1, count($markSets)), ...array_map(self::offset32(...), $markSets)));
        }

        return self::table(...$parts);
    }
}
