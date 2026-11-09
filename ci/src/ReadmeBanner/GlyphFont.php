<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_key_exists;
use function count;
use function ctype_alpha;
use function implode;
use function mb_ord;
use function mb_str_split;
use function preg_split;
use function sprintf;
use function trim;

/**
 * Sets text as SVG outlines from ci/resources/readme-banner/glyphs.json.
 *
 * GitHub shows SVG files as images and does not load web fonts for them,
 * so every text in the banner is drawn as a path.
 */
final class GlyphFont
{
    /**
     * @param array<string, array{0: int, 1: string}> $glyphs advance width and outline per character, in font units
     * @param array<string, int> $kerning adjustment per character pair, in font units
     */
    public function __construct(
        private readonly string $name,
        private readonly int $unitsPerEm,
        private readonly array $glyphs,
        private readonly array $kerning,
    ) {
    }

    public static function fromTable(string $name, array $table): self
    {
        return new self($name, $table['unitsPerEm'], $table['glyphs'], $table['kerning']);
    }

    public function width(string $text, float $size, float $tracking = 0.0): float
    {
        $width = 0.0;
        $characters = $this->characters($text);
        foreach ($characters as $i => $character) {
            $width += $this->glyphs[$character][0] + $this->kern($characters, $i);
        }
        return $width * $size / $this->unitsPerEm + $tracking * (count($characters) - 1);
    }

    /**
     * Returns the outline of $text with its baseline at $y.
     *
     * @param 'start'|'middle'|'end' $anchor which point of the text $x refers to
     */
    public function path(string $text, float $x, float $y, float $size, float $tracking = 0.0, string $anchor = 'start'): string
    {
        if ($anchor !== 'start') {
            $x -= $this->width($text, $size, $tracking) / ($anchor === 'middle' ? 2 : 1);
        }
        $scale = $size / $this->unitsPerEm;
        $path = [];
        $characters = $this->characters($text);
        foreach ($characters as $i => $character) {
            [$advance, $outline] = $this->glyphs[$character];
            if ($outline !== '') {
                $path[] = $this->place($outline, $x, $y, $scale);
            }
            $x += ($advance + $this->kern($characters, $i)) * $scale + $tracking;
        }
        return implode(' ', $path);
    }

    /**
     * @return list<string>
     */
    private function characters(string $text): array
    {
        $characters = $text === '' ? [] : mb_str_split($text);
        foreach ($characters as $character) {
            if (!array_key_exists($character, $this->glyphs)) {
                throw new RuntimeException(sprintf(
                    'The banner font "%s" has no glyph for "%s" (U+%04X) in "%s". '
                    . 'Use another character or add it to ci/resources/readme-banner/glyphs.json (see ci/resources/readme-banner/README.md).',
                    $this->name,
                    $character,
                    mb_ord($character),
                    $text,
                ));
            }
        }
        return $characters;
    }

    /**
     * @param list<string> $characters
     */
    private function kern(array $characters, int $i): int
    {
        if (!isset($characters[$i + 1])) {
            return 0;
        }
        return $this->kerning[$characters[$i] . $characters[$i + 1]] ?? 0;
    }

    /**
     * Moves a glyph outline (font units, y up) to $x/$y (pixels, y down).
     * The outlines only contain absolute M, L, Q, C and Z commands, so every
     * pair of numbers is a point.
     */
    private function place(string $outline, float $x, float $y, float $scale): string
    {
        $isX = true;
        $tokens = preg_split('/\s+/', trim($outline));
        foreach ($tokens as $i => $token) {
            if (ctype_alpha($token)) {
                $isX = true;
                continue;
            }
            $tokens[$i] = Svg::number($isX ? $x + (float)$token * $scale : $y - (float)$token * $scale);
            $isX = !$isX;
        }
        return implode(' ', $tokens);
    }
}
