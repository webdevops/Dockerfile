<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_map;
use function array_slice;
use function ceil;
use function count;
use function floor;
use function implode;
use function in_array;
use function intdiv;
use function max;
use function sprintf;
use function str_replace;

/**
 * Draws the README banner: an example image tag taken apart into web server,
 * variant, PHP version and base OS, with all options of each part.
 */
final class BannerRenderer
{
    public const THEMES = [
        'light' => [
            'background' => '#f6f8fa', 'border' => '#d0d7de', 'card' => '#ffffff', 'cardBorder' => '#d0d7de',
            'text' => '#1f2328', 'muted' => '#59636e', 'chipBorder' => '#d0d7de', 'onAccent' => '#ffffff',
            'flavor' => '#0969da', 'variant' => '#8250df', 'version' => '#1a7f37', 'base' => '#bc4c00',
        ],
        'dark' => [
            'background' => '#151b23', 'border' => '#3d444d', 'card' => '#0d1117', 'cardBorder' => '#3d444d',
            'text' => '#f0f6fc', 'muted' => '#9198a1', 'chipBorder' => '#3d444d', 'onAccent' => '#0d1117',
            'flavor' => '#4493f8', 'variant' => '#ab7df8', 'version' => '#3fb950', 'base' => '#db6d28',
        ],
    ];

    private const WIDTH = 1280;
    private const MARGIN = 44;
    private const TAG_SIZE = 52;
    private const TAG_Y = 152;
    private const BAR_Y = 170;
    private const CARD_Y = 300;
    private const CARD_GAP = 16;
    private const CARD_PADDING = 22;
    private const CHIP_SIZE = 19;
    private const CHIP_HEIGHT = 32;
    private const ROW_PITCH = 42;
    private const DESCRIPTION_SIZE = 17;
    private const NO_SUFFIX = 'no suffix';

    /**
     * @param array<string, GlyphFont> $fonts keyed like the fonts in glyphs.json
     * @param array<string, mixed> $texts conf/readme-banner.yml
     */
    public function __construct(
        private readonly ImageMatrix $matrix,
        private readonly array $texts,
        private readonly array $fonts,
    ) {
    }

    public function render(string $theme): string
    {
        $colors = self::THEMES[$theme];
        $cards = $this->cards();
        $rows = max(array_map(fn (array $card): int => count($card['rows']), $cards));
        $cardHeight = 60 + ($rows - 1) * self::ROW_PITCH + self::CHIP_HEIGHT + 14;
        $footerY = self::CARD_Y + $cardHeight + 50;
        $height = $footerY + 40;

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-labelledby="t d">',
            self::WIDTH, $height, self::WIDTH, $height,
        );
        $svg[] = '<title id="t">' . Svg::escape($this->texts['title']) . '</title>';
        $svg[] = '<desc id="d">' . Svg::escape($this->description()) . '</desc>';
        $svg[] = sprintf(
            '<rect x="1" y="1" width="%d" height="%d" rx="20" fill="%s" stroke="%s" stroke-width="2"/>',
            self::WIDTH - 2, $height - 2, $colors['background'], $colors['border'],
        );
        $svg[] = $this->text('sans-semibold', $this->texts['heading'], self::WIDTH / 2, 76, 15, $colors['muted'], 2.6, 'middle');

        $segmentCenters = $this->tag($svg, $colors);

        $cardWidth = (self::WIDTH - 2 * self::MARGIN - 3 * self::CARD_GAP) / 4;
        foreach ($cards as $i => $card) {
            $color = $colors[$card['part']];
            $left = self::MARGIN + $i * ($cardWidth + self::CARD_GAP);
            $this->connector($svg, $segmentCenters[$card['part']], $left + $cardWidth / 2, $color);
            $svg[] = sprintf(
                '<rect x="%s" y="%d" width="%s" height="%d" rx="12" fill="%s" stroke="%s" stroke-width="1.5"/>',
                Svg::number($left), self::CARD_Y, Svg::number($cardWidth), $cardHeight, $colors['card'], $colors['cardBorder'],
            );
            $svg[] = $this->text('sans-semibold', $card['title'], $left + self::CARD_PADDING, self::CARD_Y + 38, 15, $color, 1.6);
            $inner = $cardWidth - 2 * self::CARD_PADDING;
            foreach ($card['rows'] as $r => $row) {
                $x = $left + self::CARD_PADDING;
                $y = self::CARD_Y + 60 + $r * self::ROW_PITCH;
                foreach ($row as $k => $item) {
                    if ($k > 0) {
                        $x += 8;
                    }
                    $x += $this->chip($svg, $colors, $color, $x, $y, $item['chip'], $item['selected'], $item['compact']);
                    if ($item['description'] !== null) {
                        $x += 12;
                        $font = $item['selected'] ? 'sans-semibold' : 'sans-regular';
                        $textColor = $item['selected'] ? $colors['text'] : $colors['muted'];
                        $svg[] = $this->text($font, $item['description'], $x, $y + self::CHIP_HEIGHT / 2 + 6.5, self::DESCRIPTION_SIZE, $textColor);
                        $x += $this->fonts[$font]->width($item['description'], self::DESCRIPTION_SIZE);
                    }
                }
                $this->assertFits($x - $left - self::CARD_PADDING, $inner, sprintf('a row of the "%s" card', $card['title']));
            }
        }

        $this->footer($svg, $colors, $footerY);
        $svg[] = '</svg>';
        return implode("\n", $svg) . "\n";
    }

    /**
     * Content of the four cards. Each row holds one or more items.
     *
     * @return list<array{part: string, title: string, rows: list<list<array{chip: ?string, description: ?string, selected: bool, compact: bool}>>}>
     */
    private function cards(): array
    {
        $example = $this->example();
        $item = fn (?string $chip, ?string $description, bool $selected, bool $compact = false): array =>
            ['chip' => $chip, 'description' => $description, 'selected' => $selected, 'compact' => $compact];

        $flavors = [];
        foreach ($this->matrix->flavors() as $flavor) {
            if (!isset($this->texts['flavors'][$flavor])) {
                throw new RuntimeException(sprintf(
                    'The README banner has no description for the image "%s". Add one under "flavors" in conf/readme-banner.yml.',
                    $flavor,
                ));
            }
            $flavors[] = [$item($flavor, $this->texts['flavors'][$flavor], $flavor === $example['flavor'])];
        }

        $versions = [];
        $chipsPerRow = $this->versionChipsPerRow();
        foreach ($this->matrix->versions() as $i => $version) {
            $versions[intdiv($i, $chipsPerRow)][] = $item($version, null, $version === $example['version'], true);
        }

        $debian = $this->texts['bases']['debian'];
        if ($this->matrix->debianRelease() !== null) {
            $debian .= ' ' . $this->matrix->debianRelease();
        }

        return [
            ['part' => 'flavor', 'title' => $this->texts['cards']['flavor'], 'rows' => $flavors],
            ['part' => 'variant', 'title' => $this->texts['cards']['variant'], 'rows' => [
                [$item(null, $this->texts['variants']['production'], false)],
                [$item('-dev', $this->texts['variants']['dev'], true)],
            ]],
            ['part' => 'version', 'title' => $this->texts['cards']['version'], 'rows' => $versions],
            ['part' => 'base', 'title' => $this->texts['cards']['base'], 'rows' => [
                [$item(null, $debian, false)],
                [$item('-alpine', $this->texts['bases']['alpine'], true)],
            ]],
        ];
    }

    /**
     * @return array{flavor: string, version: string}
     */
    private function example(): array
    {
        $flavor = $this->texts['example']['flavor'];
        if (!in_array($flavor, $this->matrix->flavors(), true)) {
            throw new RuntimeException(sprintf(
                'The README banner example uses "%s", which is not built. Change "example" in conf/readme-banner.yml.',
                $flavor,
            ));
        }
        if (!$this->matrix->hasDevVariant() || !$this->matrix->hasAlpine()) {
            throw new RuntimeException('The README banner needs -dev and -alpine images for its example tag.');
        }
        return ['flavor' => $flavor, 'version' => $this->matrix->newestVersion()];
    }

    private function versionChipsPerRow(): int
    {
        $inner = (self::WIDTH - 2 * self::MARGIN - 3 * self::CARD_GAP) / 4 - 2 * self::CARD_PADDING;
        $widest = max(array_map(fn (string $v): float => $this->chipWidth($v, true), $this->matrix->versions()));
        return max(1, (int)(($inner + 8) / ($widest + 8)));
    }

    /**
     * Draws the example tag and returns the horizontal center of each coloured part.
     *
     * @return array<string, float>
     */
    private function tag(array &$svg, array $colors): array
    {
        $example = $this->example();
        $segments = [
            ['webdevops/', null],
            [$example['flavor'], 'flavor'],
            ['-dev', 'variant'],
            [':', null],
            [$example['version'], 'version'],
            ['-alpine', 'base'],
        ];
        $font = $this->fonts['mono-bold'];
        $total = $font->width(implode('', array_map(fn (array $s): string => $s[0], $segments)), self::TAG_SIZE);
        $this->assertFits($total, self::WIDTH - 2 * self::MARGIN, 'the example tag');

        $x = (self::WIDTH - $total) / 2;
        $centers = [];
        foreach ($segments as [$text, $part]) {
            $width = $font->width($text, self::TAG_SIZE);
            $color = $part === null ? $colors['muted'] : $colors[$part];
            $svg[] = sprintf('<path fill="%s" d="%s"/>', $color, $font->path($text, $x, self::TAG_Y, self::TAG_SIZE));
            if ($part !== null) {
                $centers[$part] = $x + $width / 2;
                $svg[] = sprintf(
                    '<rect x="%s" y="%d" width="%s" height="6" rx="3" fill="%s"/>',
                    Svg::number($x + 4), self::BAR_Y, Svg::number($width - 8), $color,
                );
            }
            $x += $width;
        }
        return $centers;
    }

    private function connector(array &$svg, float $from, float $to, string $color): void
    {
        $top = self::BAR_Y + 14;
        $bottom = self::CARD_Y - 6;
        $middle = ($top + $bottom) / 2;
        $svg[] = sprintf(
            '<path d="M%1$s %2$d C%1$s %3$s %4$s %3$s %4$s %5$d" fill="none" stroke="%6$s" stroke-width="2.5" stroke-linecap="round" opacity="0.75"/>',
            Svg::number($from), $top, Svg::number($middle), Svg::number($to), $bottom, $color,
        );
        $svg[] = sprintf('<circle cx="%s" cy="%d" r="4" fill="%s"/>', Svg::number($from), $top, $color);
        $svg[] = sprintf('<circle cx="%s" cy="%d" r="4" fill="%s"/>', Svg::number($to), $bottom, $color);
    }

    /**
     * Draws a chip with an image name part; null draws a dashed "no suffix" chip.
     *
     * @return float the width of the chip
     */
    private function chip(array &$svg, array $colors, string $color, float $x, float $y, ?string $text, bool $selected, bool $compact): float
    {
        $width = $this->chipWidth($text, $compact);
        if ($selected) {
            $svg[] = sprintf(
                '<rect x="%s" y="%s" width="%s" height="%d" rx="8" fill="%s"/>',
                Svg::number($x), Svg::number($y), Svg::number($width), self::CHIP_HEIGHT, $color,
            );
        } else {
            $svg[] = sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="8" fill="none" stroke="%s" stroke-width="1.5"%s/>',
                Svg::number($x + .75), Svg::number($y + .75), Svg::number($width - 1.5), Svg::number(self::CHIP_HEIGHT - 1.5),
                $colors['chipBorder'], $text === null ? ' stroke-dasharray="4 3"' : '',
            );
        }
        if ($text === null) {
            $svg[] = $this->text('sans-medium', self::NO_SUFFIX, $x + $width / 2, $y + self::CHIP_HEIGHT / 2 + 5.5, 15, $colors['muted'], 0, 'middle');
        } else {
            $svg[] = $this->text('mono-medium', $text, $x + $width / 2, $y + self::CHIP_HEIGHT / 2 + 6.8, self::CHIP_SIZE, $selected ? $colors['onAccent'] : $colors['muted'], 0, 'middle');
        }
        return $width;
    }

    private function chipWidth(?string $text, bool $compact): float
    {
        if ($text === null) {
            return $this->fonts['sans-medium']->width(self::NO_SUFFIX, 15) + 20;
        }
        return $this->fonts['mono-medium']->width($text, self::CHIP_SIZE) + ($compact ? 14 : 20);
    }

    private function footer(array &$svg, array $colors, int $y): void
    {
        $platforms = implode(' + ', $this->matrix->platforms());
        $parts = [];
        foreach ($this->texts['footer'] as $i => $line) {
            if ($i > 0) {
                $parts[] = ['   ·   ', true];
            }
            $parts[] = [str_replace('{platforms}', $platforms, $line), false];
        }
        $font = $this->fonts['sans-medium'];
        $total = 0.0;
        foreach ($parts as [$text]) {
            $total += $font->width($text, 18);
        }
        $this->assertFits($total, self::WIDTH - 2 * self::MARGIN, 'the footer');
        $x = (self::WIDTH - $total) / 2;
        foreach ($parts as [$text, $isSeparator]) {
            $svg[] = sprintf(
                '<path fill="%s"%s d="%s"/>',
                $colors['muted'], $isSeparator ? ' opacity="0.55"' : '', $font->path($text, $x, $y, 18),
            );
            $x += $font->width($text, 18);
        }
    }

    private function text(string $font, string $text, float $x, float $y, float $size, string $color, float $tracking = 0, string $anchor = 'start'): string
    {
        return sprintf('<path fill="%s" d="%s"/>', $color, $this->fonts[$font]->path($text, $x, $y, $size, $tracking, $anchor));
    }

    private function assertFits(float $width, float $available, string $what): void
    {
        if ($width > $available + 0.5) {
            throw new RuntimeException(sprintf(
                'The README banner has no room for %s (%dpx needed, %dpx available). '
                . 'Shorten the text in conf/readme-banner.yml or adjust the layout in ci/src/ReadmeBanner/BannerRenderer.php.',
                $what, ceil($width), floor($available),
            ));
        }
    }

    private function description(): string
    {
        $example = $this->example();
        $flavors = array_map(
            fn (string $f): string => sprintf('%s (%s)', $f, $this->texts['flavors'][$f]),
            $this->matrix->flavors(),
        );
        $versions = $this->matrix->versions();
        $debian = $this->texts['bases']['debian'] . ($this->matrix->debianRelease() !== null ? ' ' . $this->matrix->debianRelease() : '');
        $footer = str_replace('{platforms}', implode(' + ', $this->matrix->platforms()), implode('. ', $this->texts['footer']));
        return sprintf(
            'Image tag anatomy using webdevops/%s-dev:%s-alpine as the example. '
            . 'Web server: %s. Variant: no suffix for %s, -dev for %s. PHP version: %s. '
            . 'Base OS: no suffix for %s, -alpine for %s. %s.',
            $example['flavor'], $example['version'],
            $this->list($flavors),
            $this->texts['variants']['production'], $this->texts['variants']['dev'],
            $this->list($versions),
            $debian, $this->texts['bases']['alpine'],
            $footer,
        );
    }

    /**
     * @param list<string> $items
     */
    private function list(array $items): string
    {
        if (count($items) < 2) {
            return implode('', $items);
        }
        return implode(', ', array_slice($items, 0, -1)) . ' or ' . $items[count($items) - 1];
    }
}
