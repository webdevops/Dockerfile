<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use function abs;
use function array_column;
use function array_keys;
use function array_merge;
use function chr;
use function count;
use function file_put_contents;
use function intdiv;
use function is_array;
use function ksort;
use function max;
use function min;
use function round;
use function sort;
use function str_pad;
use function str_repeat;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Writes small TrueType fonts with the tables TrueTypeFont reads.
 * Glyph 0 is an empty .notdef glyph.
 */
final class FontBuilder
{
    public const int CMAP_FORMAT_4 = 4;

    public const int CMAP_FORMAT_4_GLYPH_ARRAY = 40;

    public const int CMAP_FORMAT_12 = 12;

    private const int ON_CURVE = 0x01;

    private const int X_SHORT = 0x02;

    private const int Y_SHORT = 0x04;

    private const int REPEAT = 0x08;

    private const int X_SAME_OR_POSITIVE = 0x10;

    private const int Y_SAME_OR_POSITIVE = 0x20;

    private const int CUBIC = 0x80;

    /** @var list<array{advance: int, leftSideBearing: int, data: string}> */
    private array $glyphs = [['advance' => 500, 'leftSideBearing' => 0, 'data' => '']];

    /** @var array<int, int> glyph per code point */
    private array $cmap = [];

    private int $cmapFormat = self::CMAP_FORMAT_4;

    private ?string $rawCmap = null;

    private bool $longLoca = false;

    private ?int $numberOfHMetrics = null;

    private string $version = "\x00\x01\x00\x00";

    /** @var array<string, ?string> extra tables, null leaves out a required table */
    private array $tables = [];

    public function __construct(private readonly int $unitsPerEm = 1000)
    {
    }

    /**
     * A glyph with quadratic contours. Each point is x, y and optionally false for an
     * off-curve point or "cubic" for an off-curve point with the cubic flag.
     *
     * @param list<list<array{0: int, 1: int, 2?: bool|'cubic'}>> $contours
     */
    public function simpleGlyph(int $advance, array $contours, ?int $leftSideBearing = null): int
    {
        $points = array_merge(...$contours);
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $xMin = $xs === [] ? 0 : min($xs);
        $data = OpenType::int16(count($contours), $xMin, $ys === [] ? 0 : min($ys), $xs === [] ? 0 : max($xs), $ys === [] ? 0 : max($ys));
        $end = -1;
        foreach ($contours as $contour) {
            $end += count($contour);
            $data .= OpenType::uint16($end);
        }

        $data .= OpenType::uint16(0); // no instructions

        $flags = [];
        $xBytes = '';
        $yBytes = '';
        $previousX = 0;
        $previousY = 0;
        foreach ($points as $point) {
            $type = $point[2] ?? true;
            $flag = $type === true ? self::ON_CURVE : ($type === 'cubic' ? self::CUBIC : 0);
            [$xFlag, $bytes] = $this->coordinate($point[0] - $previousX, self::X_SHORT, self::X_SAME_OR_POSITIVE);
            $xBytes .= $bytes;
            [$yFlag, $bytes] = $this->coordinate($point[1] - $previousY, self::Y_SHORT, self::Y_SAME_OR_POSITIVE);
            $yBytes .= $bytes;
            $flags[] = $flag | $xFlag | $yFlag;
            [$previousX, $previousY] = $point;
        }

        // Runs of the same flag are written once with a repeat count
        $i = 0;
        while ($i < count($flags)) {
            $run = 1;
            while ($i + $run < count($flags) && $flags[$i + $run] === $flags[$i] && $run < 256) {
                $run++;
            }

            $data .= $run > 1 ? chr($flags[$i] | self::REPEAT) . chr($run - 1) : chr($flags[$i]);
            $i += $run;
        }

        $this->glyphs[] = ['advance' => $advance, 'leftSideBearing' => $leftSideBearing ?? $xMin, 'data' => $data . $xBytes . $yBytes];
        return count($this->glyphs) - 1;
    }

    /**
     * A glyph made of other glyphs, each moved by dx/dy and optionally scaled:
     * one scale, an x and a y scale, or a 2x2 matrix xx, xy, yx, yy.
     *
     * @param list<array{glyph: int, dx: int, dy: int, scale?: float|list<float>, matchPoints?: bool}> $components
     */
    public function compositeGlyph(int $advance, array $components): int
    {
        $data = OpenType::int16(-1, 0, 0, 0, 0);
        foreach ($components as $i => $component) {
            $words = max(abs($component['dx']), abs($component['dy'])) > 127;
            $scale = $component['scale'] ?? [];
            $scale = is_array($scale) ? $scale : [$scale];
            $flags = ($component['matchPoints'] ?? false) ? 0 : 0x0002;
            $flags |= $words ? 0x0001 : 0;
            $flags |= [0 => 0, 1 => 0x0008, 2 => 0x0040, 4 => 0x0080][count($scale)];
            $flags |= $i < count($components) - 1 ? 0x0020 : 0;
            $data .= OpenType::uint16($flags, $component['glyph']);
            $data .= $words ? OpenType::int16($component['dx'], $component['dy']) : OpenType::int8($component['dx'], $component['dy']);
            foreach ($scale as $value) {
                $data .= OpenType::int16((int)round($value * 16384));
            }
        }

        $this->glyphs[] = ['advance' => $advance, 'leftSideBearing' => 0, 'data' => $data];
        return count($this->glyphs) - 1;
    }

    public function emptyGlyph(int $advance): int
    {
        $this->glyphs[] = ['advance' => $advance, 'leftSideBearing' => 0, 'data' => ''];
        return count($this->glyphs) - 1;
    }

    public function map(int $codePoint, int $glyph): self
    {
        $this->cmap[$codePoint] = $glyph;
        return $this;
    }

    public function cmapFormat(int $format): self
    {
        $this->cmapFormat = $format;
        return $this;
    }

    /**
     * A cmap with one subtable, instead of the one built from map().
     */
    public function rawCmap(int $platform, int $encoding, string $subtable): self
    {
        $this->rawCmap = OpenType::uint16(0, 1, $platform, $encoding) . OpenType::uint32(12) . $subtable;
        return $this;
    }

    public function longLoca(): self
    {
        $this->longLoca = true;
        return $this;
    }

    public function numberOfHMetrics(int $count): self
    {
        $this->numberOfHMetrics = $count;
        return $this;
    }

    public function version(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Adds a table, or leaves out a required one with null.
     */
    public function table(string $tag, ?string $data): self
    {
        $this->tables[$tag] = $data;
        return $this;
    }

    public function build(): string
    {
        $glyf = '';
        $offsets = [];
        foreach ($this->glyphs as $glyph) {
            $offsets[] = strlen($glyf);
            $glyf .= $glyph['data'] . str_repeat("\0", (4 - strlen($glyph['data']) % 4) % 4);
        }

        $offsets[] = strlen($glyf);
        $loca = '';
        foreach ($offsets as $offset) {
            $loca .= $this->longLoca ? OpenType::uint32($offset) : OpenType::uint16(intdiv($offset, 2));
        }

        $numberOfHMetrics = $this->numberOfHMetrics ?? count($this->glyphs);
        $hmtx = '';
        foreach ($this->glyphs as $i => $glyph) {
            $hmtx .= ($i < $numberOfHMetrics ? OpenType::uint16($glyph['advance']) : '') . OpenType::int16($glyph['leftSideBearing']);
        }

        $tables = [
            'cmap' => $this->rawCmap ?? $this->cmap(),
            'glyf' => $glyf,
            // version, revision, checksum adjustment, magic number, flags, unitsPerEm, dates, bounding box,
            // macStyle, lowestRecPPEM, fontDirectionHint, indexToLocFormat, glyphDataFormat
            'head' => OpenType::uint32(0x00010000, 0, 0, 0x5F0F3CF5) . OpenType::uint16(0, $this->unitsPerEm)
                . str_repeat("\0", 24) . OpenType::uint16(0, 8, 2, $this->longLoca ? 1 : 0, 0),
            'hhea' => OpenType::uint32(0x00010000) . str_repeat("\0", 30) . OpenType::uint16($numberOfHMetrics),
            'hmtx' => $hmtx,
            'loca' => $loca,
            'maxp' => OpenType::uint32(0x00005000) . OpenType::uint16(count($this->glyphs)),
        ];
        foreach ($this->tables as $tag => $data) {
            if ($data === null) {
                unset($tables[$tag]);
            } else {
                $tables[$tag] = $data;
            }
        }

        ksort($tables);
        $directory = $this->version . OpenType::uint16(count($tables), 0, 0, 0);
        $offset = 12 + 16 * count($tables);
        $data = '';
        foreach ($tables as $tag => $table) {
            $directory .= str_pad((string)$tag, 4) . OpenType::uint32(0, $offset + strlen($data), strlen($table));
            $data .= $table . str_repeat("\0", (4 - strlen($table) % 4) % 4);
        }

        return $directory . $data;
    }

    /**
     * Writes the font to a temporary file and returns its path.
     */
    public function save(): string
    {
        $path = (string)tempnam(sys_get_temp_dir(), 'font-');
        file_put_contents($path, $this->build());
        return $path;
    }

    private function cmap(): string
    {
        $codePoints = array_keys($this->cmap);
        sort($codePoints);
        if ($this->cmapFormat === self::CMAP_FORMAT_12) {
            $groups = '';
            foreach ($codePoints as $codePoint) {
                $groups .= OpenType::uint32($codePoint, $codePoint, $this->cmap[$codePoint]);
            }

            $subtable = OpenType::uint16(12, 0) . OpenType::uint32(16 + strlen($groups), 0, count($codePoints)) . $groups;
            return OpenType::uint16(0, 1, 3, 10) . OpenType::uint32(12) . $subtable;
        }

        // One segment per code point and the closing segment at 0xFFFF
        $segments = count($codePoints) + 1;
        $glyphArray = $this->cmapFormat === self::CMAP_FORMAT_4_GLYPH_ARRAY;
        $ends = '';
        $starts = '';
        $deltas = '';
        $rangeOffsets = '';
        $glyphIds = '';
        foreach ($codePoints as $s => $codePoint) {
            $ends .= OpenType::uint16($codePoint);
            $starts .= OpenType::uint16($codePoint);
            // With the glyph array, idDelta is added to the glyph from the array
            $deltas .= OpenType::uint16($glyphArray ? -1 : $this->cmap[$codePoint] - $codePoint);
            // From the segment's own idRangeOffset field to its entry in the glyph array after all of them
            $rangeOffsets .= OpenType::uint16($glyphArray ? 2 * ($segments - $s) + 2 * $s : 0);
            $glyphIds .= $glyphArray ? OpenType::uint16($this->cmap[$codePoint] + 1) : '';
        }

        $ends .= OpenType::uint16(0xFFFF);
        $starts .= OpenType::uint16(0xFFFF);
        $deltas .= OpenType::uint16(1);
        $rangeOffsets .= OpenType::uint16(0);
        $body = OpenType::uint16(0, 2 * $segments, 0, 0, 0) . $ends . OpenType::uint16(0) . $starts . $deltas . $rangeOffsets . $glyphIds;
        $subtable = OpenType::uint16(4, 4 + strlen($body)) . $body;
        return OpenType::uint16(0, 1, 3, 1) . OpenType::uint32(12) . $subtable;
    }

    /**
     * @return array{0: int, 1: string} flag bits and bytes of a coordinate delta, as short as possible
     */
    private function coordinate(int $delta, int $short, int $sameOrPositive): array
    {
        if ($delta === 0) {
            return [$sameOrPositive, ''];
        }

        if (abs($delta) < 256) {
            return [$short | ($delta > 0 ? $sameOrPositive : 0), chr(abs($delta))];
        }

        return [0, OpenType::int16($delta)];
    }
}
