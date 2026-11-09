<?php

namespace Webdevops\Build\ReadmeBanner\Glyphs;

use RuntimeException;

use function array_map;
use function array_search;
use function array_slice;
use function count;
use function file_get_contents;
use function in_array;
use function is_file;
use function min;
use function sprintf;

/**
 * Reads character mapping, advance widths, outlines and kerning from a font
 * file with TrueType outlines (.ttf).
 *
 * @phpstan-type Transform array{0: float|int, 1: float|int, 2: float|int, 3: float|int, 4: float|int, 5: float|int}
 */
final class TrueTypeFont
{
    private const int ON_CURVE = 0x01;

    private const int X_SHORT = 0x02;

    private const int Y_SHORT = 0x04;

    private const int REPEAT = 0x08;

    private const int X_SAME_OR_POSITIVE = 0x10;

    private const int Y_SAME_OR_POSITIVE = 0x20;

    private const int CUBIC = 0x80;

    private const int ARGS_ARE_WORDS = 0x0001;

    private const int ARGS_ARE_XY_VALUES = 0x0002;

    private const int HAVE_SCALE = 0x0008;

    private const int MORE_COMPONENTS = 0x0020;

    private const int HAVE_X_AND_Y_SCALE = 0x0040;

    private const int HAVE_TWO_BY_TWO = 0x0080;

    /**
     * Same order as fontTools' getBestCmap: Unicode full repertoire first, then BMP.
     */
    private const array CMAP_PREFERENCES = [[3, 10], [0, 6], [0, 4], [3, 1], [0, 3], [0, 2], [0, 1], [0, 0]];

    private readonly BinaryData $data;

    /** @var array<string, array{0: int, 1: int}> offset and length per table tag */
    private array $tables = [];

    /** @var array<string, BinaryData> tables read so far */
    private array $tableData = [];

    private readonly int $unitsPerEm;

    private readonly int $numGlyphs;

    private readonly int $numberOfHMetrics;

    private readonly bool $longLoca;

    /** @var array<int, int> glyph id per code point */
    private array $cmap;

    private ?GposKerning $kerning = null;

    public function __construct(private readonly string $path)
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('The font file %s does not exist.', $path));
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new RuntimeException(sprintf('Cannot read the font file %s.', $path));
        }

        $this->data = new BinaryData($bytes);
        $version = $this->data->tag(0);
        if ($version === 'OTTO') {
            throw new RuntimeException(sprintf('%s has PostScript (CFF) outlines. Use the .ttf file of the font.', $path));
        }

        if ($version === 'wOFF' || $version === 'wOF2') {
            throw new RuntimeException(sprintf('%s is a compressed web font. Use the .ttf file of the font.', $path));
        }

        if ($version !== "\x00\x01\x00\x00" && $version !== 'true') {
            throw new RuntimeException(sprintf('%s is not a TrueType font (.ttf).', $path));
        }

        $numTables = $this->data->uint16(4);
        for ($i = 0; $i < $numTables; $i++) {
            $record = 12 + 16 * $i;
            $this->tables[$this->data->tag($record)] = [$this->data->uint32($record + 8), $this->data->uint32($record + 12)];
        }

        foreach (['cmap', 'glyf', 'head', 'hhea', 'hmtx', 'loca', 'maxp'] as $required) {
            if (!isset($this->tables[$required])) {
                throw new RuntimeException(sprintf('%s has no "%s" table.', $path, $required));
            }
        }

        $head = $this->table('head');
        $this->unitsPerEm = $head->uint16(18);
        $this->longLoca = $head->int16(50) === 1;
        $this->numGlyphs = $this->table('maxp')->uint16(4);
        $this->numberOfHMetrics = $this->table('hhea')->uint16(34);
        $this->cmap = $this->readCmap();
    }

    public function path(): string
    {
        return $this->path;
    }

    public function unitsPerEm(): int
    {
        return $this->unitsPerEm;
    }

    public function glyphId(int $codePoint): ?int
    {
        $glyph = $this->cmap[$codePoint] ?? 0;
        return $glyph === 0 || $glyph >= $this->numGlyphs ? null : $glyph;
    }

    public function advanceWidth(int $glyph): int
    {
        return $this->table('hmtx')->uint16(4 * (min($glyph, $this->numberOfHMetrics - 1)));
    }

    /**
     * The outline as M, L, Q and Z commands in font units, or '' for an empty glyph.
     */
    public function outline(int $glyph): string
    {
        $pen = new OutlinePen();
        $this->draw($glyph, $pen, null, 0);
        return $pen->commands();
    }

    /**
     * How much the advance of the pair changes when both glyphs are set next
     * to each other, in font units.
     */
    public function kerning(int $first, int $second, bool $latin): int
    {
        if (!isset($this->tables['GPOS'])) {
            return 0;
        }

        $this->kerning ??= new GposKerning(
            $this->table('GPOS'),
            isset($this->tables['GDEF']) ? $this->table('GDEF') : null,
            $this->path,
        );
        return $this->kerning->adjustment($first, $second, $latin);
    }

    private function table(string $tag): BinaryData
    {
        if (!isset($this->tableData[$tag])) {
            [$offset, $length] = $this->tables[$tag];
            $this->tableData[$tag] = $this->data->slice($offset, $length);
        }

        return $this->tableData[$tag];
    }

    /**
     * @return array<int, int>
     */
    private function readCmap(): array
    {
        $cmap = $this->table('cmap');
        $subtables = [];
        $count = $cmap->uint16(2);
        for ($i = 0; $i < $count; $i++) {
            $record = 4 + 8 * $i;
            $subtables[$cmap->uint16($record) . '/' . $cmap->uint16($record + 2)] = $cmap->uint32($record + 4);
        }

        foreach (self::CMAP_PREFERENCES as [$platform, $encoding]) {
            if (!isset($subtables[$platform . '/' . $encoding])) {
                continue;
            }

            $offset = $subtables[$platform . '/' . $encoding];
            return match ($cmap->uint16($offset)) {
                4 => $this->readCmapFormat4($cmap, $offset),
                12 => $this->readCmapFormat12($cmap, $offset),
                default => throw new RuntimeException(sprintf(
                    '%s maps Unicode with a cmap subtable of format %d; only the formats 4 and 12 are supported.',
                    $this->path,
                    $cmap->uint16($offset),
                )),
            };
        }

        throw new RuntimeException(sprintf('%s has no Unicode character map.', $this->path));
    }

    /**
     * @return array<int, int>
     */
    private function readCmapFormat4(BinaryData $cmap, int $offset): array
    {
        $segments = $cmap->uint16($offset + 6) / 2;
        $ends = $offset + 14;
        $starts = $ends + 2 * $segments + 2;
        $deltas = $starts + 2 * $segments;
        $rangeOffsets = $deltas + 2 * $segments;
        $map = [];
        for ($s = 0; $s < $segments; $s++) {
            $start = $cmap->uint16($starts + 2 * $s);
            $end = $cmap->uint16($ends + 2 * $s);
            $delta = $cmap->uint16($deltas + 2 * $s);
            $rangeOffset = $cmap->uint16($rangeOffsets + 2 * $s);
            for ($codePoint = $start; $codePoint <= $end && $codePoint !== 0xFFFF; $codePoint++) {
                if ($rangeOffset === 0) {
                    $glyph = ($codePoint + $delta) & 0xFFFF;
                } else {
                    $glyph = $cmap->uint16($rangeOffsets + 2 * $s + $rangeOffset + 2 * ($codePoint - $start));
                    if ($glyph !== 0) {
                        $glyph = ($glyph + $delta) & 0xFFFF;
                    }
                }

                $map[$codePoint] = $glyph;
            }
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function readCmapFormat12(BinaryData $cmap, int $offset): array
    {
        $map = [];
        $groups = $cmap->uint32($offset + 12);
        for ($g = 0; $g < $groups; $g++) {
            $group = $offset + 16 + 12 * $g;
            $start = $cmap->uint32($group);
            $end = $cmap->uint32($group + 4);
            $glyph = $cmap->uint32($group + 8);
            for ($codePoint = $start; $codePoint <= $end; $codePoint++) {
                $map[$codePoint] = $glyph + $codePoint - $start;
            }
        }

        return $map;
    }

    /**
     * Draws a glyph the way fontTools does, so the outlines match its pens:
     * components are drawn with their transformation, and each contour starts
     * at its last on-curve point.
     *
     * @param ?Transform $transform xx, xy, yx, yy, dx, dy
     */
    private function draw(int $glyph, OutlinePen $pen, ?array $transform, int $depth): void
    {
        if ($depth > 16) {
            throw new RuntimeException(sprintf('%s has components nested too deep in glyph %d.', $this->path, $glyph));
        }

        $data = $this->glyphData($glyph);
        if ($data === null) {
            return;
        }

        $contours = $data->int16(0);
        if ($contours < 0) {
            foreach ($this->components($data) as [$component, $componentTransform]) {
                $combined = $transform === null ? $componentTransform : $this->combine($transform, $componentTransform);
                $this->draw($component, $pen, $combined, $depth + 1);
            }

            return;
        }

        [$points, $flags, $endPoints] = $this->simpleGlyph($data, $contours);
        // fontTools moves the outline if the left side bearing differs from xMin, but only for the glyph itself, not for components
        $offset = $depth === 0 ? $this->leftSideBearing($glyph) - $data->int16(2) : 0;
        foreach ($points as $i => [$x, $y]) {
            if ($offset !== 0) {
                $x += $offset;
            }

            $points[$i] = $transform === null ? [$x, $y] : [
                $transform[0] * $x + $transform[2] * $y + $transform[4],
                $transform[1] * $x + $transform[3] * $y + $transform[5],
            ];
        }

        $start = 0;
        foreach ($endPoints as $end) {
            $contour = array_slice($points, $start, $end + 1 - $start);
            $onCurve = array_map(fn (int $flag): bool => ($flag & self::ON_CURVE) !== 0, array_slice($flags, $start, $end + 1 - $start));
            $start = $end + 1;
            if ($contour === []) {
                continue;
            }

            if (!in_array(true, $onCurve, true)) {
                $pen->qCurveTo($contour, true);
                $pen->closePath();
                continue;
            }

            // Rotate the contour so it ends with an on-curve point, which is where it starts
            $first = array_search(true, $onCurve, true) + 1;
            $contour = [...array_slice($contour, $first), ...array_slice($contour, 0, $first)];
            $onCurve = [...array_slice($onCurve, $first), ...array_slice($onCurve, 0, $first)];
            $pen->moveTo($contour[count($contour) - 1]);
            while ($contour !== []) {
                $next = array_search(true, $onCurve, true) + 1;
                if ($next === 1) {
                    // The last line is drawn by closing the path
                    if (count($contour) > 1) {
                        $pen->lineTo($contour[0]);
                    }
                } else {
                    $pen->qCurveTo(array_slice($contour, 0, $next));
                }

                $contour = array_slice($contour, $next);
                $onCurve = array_slice($onCurve, $next);
            }

            $pen->closePath();
        }
    }

    private function glyphData(int $glyph): ?BinaryData
    {
        if ($glyph >= $this->numGlyphs) {
            throw new RuntimeException(sprintf('%s refers to glyph %d, but has only %d glyphs.', $this->path, $glyph, $this->numGlyphs));
        }

        $loca = $this->table('loca');
        if ($this->longLoca) {
            $start = $loca->uint32(4 * $glyph);
            $end = $loca->uint32(4 * $glyph + 4);
        } else {
            $start = 2 * $loca->uint16(2 * $glyph);
            $end = 2 * $loca->uint16(2 * $glyph + 2);
        }

        if ($end <= $start) {
            return null;
        }

        [$glyfOffset] = $this->tables['glyf'];
        return $this->data->slice($glyfOffset + $start, $end - $start);
    }

    private function leftSideBearing(int $glyph): int
    {
        $hmtx = $this->table('hmtx');
        if ($glyph < $this->numberOfHMetrics) {
            return $hmtx->int16(4 * $glyph + 2);
        }

        return $hmtx->int16(4 * $this->numberOfHMetrics + 2 * ($glyph - $this->numberOfHMetrics));
    }

    /**
     * @return array{0: list<array{0: int, 1: int}>, 1: list<int>, 2: list<int>} points, flags and the last point of each contour
     */
    private function simpleGlyph(BinaryData $data, int $contours): array
    {
        $endPoints = [];
        for ($c = 0; $c < $contours; $c++) {
            $endPoints[] = $data->uint16(10 + 2 * $c);
        }

        $count = $endPoints === [] ? 0 : $endPoints[$contours - 1] + 1;
        $offset = 10 + 2 * $contours;
        $offset += 2 + $data->uint16($offset);

        $flags = [];
        while (count($flags) < $count) {
            $flag = $data->uint8($offset++);
            $repeat = ($flag & self::REPEAT) !== 0 ? $data->uint8($offset++) : 0;
            for ($r = 0; $r <= $repeat; $r++) {
                $flags[] = $flag;
            }

            if (($flag & self::CUBIC) !== 0 && ($flag & self::ON_CURVE) === 0) {
                throw new RuntimeException(sprintf('%s has cubic TrueType outlines, which are not supported.', $this->path));
            }
        }

        $xs = [];
        $x = 0;
        foreach ($flags as $flag) {
            if (($flag & self::X_SHORT) !== 0) {
                $delta = $data->uint8($offset++);
                $x += ($flag & self::X_SAME_OR_POSITIVE) !== 0 ? $delta : -$delta;
            } elseif (($flag & self::X_SAME_OR_POSITIVE) === 0) {
                $x += $data->int16($offset);
                $offset += 2;
            }

            $xs[] = $x;
        }

        $points = [];
        $y = 0;
        foreach ($flags as $i => $flag) {
            if (($flag & self::Y_SHORT) !== 0) {
                $delta = $data->uint8($offset++);
                $y += ($flag & self::Y_SAME_OR_POSITIVE) !== 0 ? $delta : -$delta;
            } elseif (($flag & self::Y_SAME_OR_POSITIVE) === 0) {
                $y += $data->int16($offset);
                $offset += 2;
            }

            $points[] = [$xs[$i], $y];
        }

        return [$points, array_slice($flags, 0, $count), $endPoints];
    }

    /**
     * @return list<array{0: int, 1: Transform}> glyph and transformation of each component
     */
    private function components(BinaryData $data): array
    {
        $components = [];
        $offset = 10;
        do {
            $flags = $data->uint16($offset);
            $glyph = $data->uint16($offset + 2);
            $offset += 4;
            if (($flags & self::ARGS_ARE_XY_VALUES) === 0) {
                throw new RuntimeException(sprintf(
                    '%s positions a component of a glyph by matching points, which is not supported.',
                    $this->path,
                ));
            }

            if (($flags & self::ARGS_ARE_WORDS) !== 0) {
                $dx = $data->int16($offset);
                $dy = $data->int16($offset + 2);
                $offset += 4;
            } else {
                $dx = $data->int8($offset);
                $dy = $data->int8($offset + 1);
                $offset += 2;
            }

            $matrix = [1, 0, 0, 1];
            if (($flags & self::HAVE_SCALE) !== 0) {
                $scale = $data->f2dot14($offset);
                $matrix = [$scale, 0, 0, $scale];
                $offset += 2;
            } elseif (($flags & self::HAVE_X_AND_Y_SCALE) !== 0) {
                $matrix = [$data->f2dot14($offset), 0, 0, $data->f2dot14($offset + 2)];
                $offset += 4;
            } elseif (($flags & self::HAVE_TWO_BY_TWO) !== 0) {
                $matrix = [$data->f2dot14($offset), $data->f2dot14($offset + 2), $data->f2dot14($offset + 4), $data->f2dot14($offset + 6)];
                $offset += 8;
            }

            $components[] = [$glyph, [...$matrix, $dx, $dy]];
        } while (($flags & self::MORE_COMPONENTS) !== 0);

        return $components;
    }

    /**
     * The transformation that applies $inner first and then $outer, computed like fontTools' Transform.transform().
     *
     * @param Transform $outer
     * @param Transform $inner
     * @return Transform
     */
    private function combine(array $outer, array $inner): array
    {
        [$xx1, $xy1, $yx1, $yy1, $dx1, $dy1] = $inner;
        [$xx2, $xy2, $yx2, $yy2, $dx2, $dy2] = $outer;
        return [
            $xx1 * $xx2 + $xy1 * $yx2,
            $xx1 * $xy2 + $xy1 * $yy2,
            $yx1 * $xx2 + $yy1 * $yx2,
            $yx1 * $xy2 + $yy1 * $yy2,
            $xx2 * $dx1 + $yx2 * $dy1 + $dx2,
            $xy2 * $dx1 + $yy2 * $dy1 + $dy2,
        ];
    }
}
