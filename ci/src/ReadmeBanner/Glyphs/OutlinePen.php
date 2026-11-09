<?php

namespace Webdevops\Build\ReadmeBanner\Glyphs;

use Webdevops\Build\ReadmeBanner\Svg;

use function count;
use function implode;

/**
 * Collects a glyph outline as absolute M, L, Q and Z commands in font units, y axis pointing up.
 *
 * Quadratic curves with several off-curve points are split at the implied
 * on-curve points between them, so every Q command has one control point.
 */
final class OutlinePen
{
    /** @var list<string> */
    private array $parts = [];

    /**
     * @param array{0: float, 1: float} $point
     */
    public function moveTo(array $point): void
    {
        $this->add('M', $point);
    }

    /**
     * @param array{0: float, 1: float} $point
     */
    public function lineTo(array $point): void
    {
        $this->add('L', $point);
    }

    /**
     * Off-curve points followed by the on-curve end point. Without an end point
     * the contour has no on-curve point at all and starts and ends between its
     * last and first off-curve point.
     *
     * @param list<array{0: float, 1: float}> $points
     */
    public function qCurveTo(array $points, bool $closedWithoutOnCurve = false): void
    {
        if ($closedWithoutOnCurve) {
            $start = $this->middle($points[count($points) - 1], $points[0]);
            $this->moveTo($start);
            $points[] = $start;
        }

        if (count($points) === 1) {
            $this->lineTo($points[0]);
            return;
        }

        $last = count($points) - 1;
        for ($i = 0; $i < $last - 1; $i++) {
            $this->add('Q', $points[$i], $this->middle($points[$i], $points[$i + 1]));
        }

        $this->add('Q', $points[$last - 1], $points[$last]);
    }

    public function closePath(): void
    {
        $this->parts[] = 'Z';
    }

    public function commands(): string
    {
        return implode(' ', $this->parts);
    }

    /**
     * @param array{0: float, 1: float} ...$points
     */
    private function add(string $command, array ...$points): void
    {
        $part = $command;
        foreach ($points as [$x, $y]) {
            $part .= ' ' . Svg::number($x) . ' ' . Svg::number($y);
        }

        $this->parts[] = $part;
    }

    /**
     * @param array{0: float, 1: float} $a
     * @param array{0: float, 1: float} $b
     * @return array{0: float, 1: float}
     */
    private function middle(array $a, array $b): array
    {
        return [0.5 * ($a[0] + $b[0]), 0.5 * ($a[1] + $b[1])];
    }
}
