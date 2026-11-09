<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_chunk;
use function array_key_first;
use function array_key_last;
use function array_map;
use function array_values;
use function is_string;
use function array_slice;
use function ceil;
use function count;
use function floor;
use function implode;
use function in_array;
use function is_array;
use function max;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Draws the README banner: an example image tag taken apart into web server,
 * variant, PHP version and base OS, with all options of each part.
 *
 * @phpstan-type Item array{chip: ?string, description: ?string, selected: bool, compact: bool}
 * @phpstan-type Row array{items: list<Item>, note: ?string}
 * @phpstan-type Card array{part: string, title: string, rows: list<Row>}
 */
final class BannerRenderer
{
    public const array THEMES = [
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

    private const int WIDTH = 1280;

    private const int MARGIN = 44;

    private const int TAG_SIZE = 52;

    private const int TAG_Y = 152;

    private const int BAR_Y = 170;

    private const int CARD_Y = 300;

    private const int CARD_GAP = 16;

    private const int CARD_PADDING = 22;

    private const int CHIP_SIZE = 19;

    private const int CHIP_HEIGHT = 32;

    private const int ROW_PITCH = 42;

    private const int DESCRIPTION_SIZE = 17;

    private const int NOTE_SIZE = 15;

    private const int NOTE_HEIGHT = 20;

    private const int NOTE_BASELINE = 12;

    private const int NOTE_GAP = 6;

    private const string NO_SUFFIX_LABEL = 'no suffix';

    /** @var ?array{flavor: string, version: string, base: string} */
    private ?array $example = null;

    /** @var ?list<array{suffix: string, text: string, notes: list<string>}> */
    private ?array $bases = null;

    /**
     * @param array<string, GlyphFont> $fonts at least GlyphFont::FONTS
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
        $layouts = array_map(fn (array $card): array => $this->rowLayout($card['rows']), $cards);
        $cardHeight = 60 + max(0, ...array_map(fn (array $layout): int => $layout['height'], $layouts)) + 14;
        $footerY = self::CARD_Y + $cardHeight + 50;
        $height = $footerY + 40;

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-labelledby="t d">',
            self::WIDTH,
            $height,
            self::WIDTH,
            $height,
        );
        $svg[] = '<title id="t">' . Svg::escape($this->settingText('title')) . '</title>';
        $svg[] = '<desc id="d">' . Svg::escape($this->description()) . '</desc>';
        $svg[] = sprintf(
            '<rect x="1" y="1" width="%d" height="%d" rx="20" fill="%s" stroke="%s" stroke-width="2"/>',
            self::WIDTH - 2,
            $height - 2,
            $colors['background'],
            $colors['border'],
        );
        $svg[] = $this->text('sans-semibold', $this->settingText('heading'), self::WIDTH / 2, 76, 15, $colors['muted'], 2.6, 'middle');

        $segmentCenters = $this->tag($svg, $colors);

        $cardWidth = (self::WIDTH - 2 * self::MARGIN - 3 * self::CARD_GAP) / 4;
        foreach ($cards as $i => $card) {
            $color = $colors[$card['part']];
            $left = self::MARGIN + $i * ($cardWidth + self::CARD_GAP);
            $this->connector($svg, $segmentCenters[$card['part']], $left + $cardWidth / 2, $color);
            $svg[] = sprintf(
                '<rect x="%s" y="%d" width="%s" height="%d" rx="12" fill="%s" stroke="%s" stroke-width="1.5"/>',
                Svg::number($left),
                self::CARD_Y,
                Svg::number($cardWidth),
                $cardHeight,
                $colors['card'],
                $colors['cardBorder'],
            );
            $svg[] = $this->text('sans-semibold', $card['title'], $left + self::CARD_PADDING, self::CARD_Y + 38, 15, $color, 1.6);
            $inner = $cardWidth - 2 * self::CARD_PADDING;
            $descriptionX = null;
            foreach ($card['rows'] as $r => $row) {
                $x = $left + self::CARD_PADDING;
                $y = self::CARD_Y + 60 + $layouts[$i]['tops'][$r];
                if ($row['note'] !== null) {
                    // A note explains the description above it, so it starts below that description
                    $x = $descriptionX ?? $x;
                    $svg[] = $this->text('sans-regular', $row['note'], $x, $y + self::NOTE_BASELINE, self::NOTE_SIZE, $colors['muted']);
                    $x += $this->fonts['sans-regular']->width($row['note'], self::NOTE_SIZE);
                } else {
                    foreach ($row['items'] as $k => $item) {
                        if ($k > 0) {
                            $x += 8;
                        }

                        $x += $this->chip($svg, $colors, $color, $x, $y, $item['chip'], $item['selected'], $item['compact']);
                        if ($item['description'] !== null) {
                            $x += 12;
                            $descriptionX = $x;
                            $font = $item['selected'] ? 'sans-semibold' : 'sans-regular';
                            $textColor = $item['selected'] ? $colors['text'] : $colors['muted'];
                            $svg[] = $this->text($font, $item['description'], $x, $y + self::CHIP_HEIGHT / 2 + 6.5, self::DESCRIPTION_SIZE, $textColor);
                            $x += $this->fonts[$font]->width($item['description'], self::DESCRIPTION_SIZE);
                        }
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
     * Top of each row relative to the first one, and the height of all rows.
     * A note sits right below the row it belongs to.
     *
     * @param list<Row> $rows
     * @return array{tops: list<int>, height: int}
     */
    private function rowLayout(array $rows): array
    {
        $tops = [];
        $y = 0;
        foreach ($rows as $r => $row) {
            $tops[] = $y;
            $isNote = $row['note'] !== null;
            $y += $isNote ? self::NOTE_HEIGHT : self::CHIP_HEIGHT;
            if (isset($rows[$r + 1]) && $rows[$r + 1]['note'] === null) {
                $y += $isNote ? self::NOTE_GAP : self::ROW_PITCH - self::CHIP_HEIGHT;
            }
        }

        return ['tops' => $tops, 'height' => $y];
    }

    /**
     * Content of the four cards. Each row holds one or more items, or a note in smaller text.
     *
     * @return list<Card>
     */
    private function cards(): array
    {
        $example = $this->example();
        $item = fn (?string $chip, ?string $description, bool $selected, bool $compact = false): array =>
            ['chip' => $chip, 'description' => $description, 'selected' => $selected, 'compact' => $compact];
        $row = $this->row(...);

        $flavors = [];
        foreach ($this->matrix->flavors() as $flavor) {
            if (!isset($this->texts['flavors'][$flavor])) {
                throw new RuntimeException(sprintf(
                    'The README banner has no description for the image "%s". Add one under "flavors" in conf/readme-banner.yml.',
                    $flavor,
                ));
            }

            $flavors[] = $row($item($flavor, $this->texts['flavors'][$flavor], $flavor === $example['flavor']));
        }

        $versions = [];
        foreach (array_chunk($this->matrix->versions(), $this->versionChipsPerRow()) as $chunk) {
            $versions[] = $row(...array_map(fn (string $version): array => $item($version, null, $version === $example['version'], true), $chunk));
        }

        $bases = [];
        foreach ($this->bases() as $base) {
            $chip = $base['suffix'] === ImageMatrix::NO_SUFFIX ? null : '-' . $base['suffix'];
            $bases[] = $row($item($chip, $base['text'], $base['suffix'] === $example['base']));
            foreach ($base['notes'] as $note) {
                $bases[] = ['items' => [], 'note' => $note];
            }
        }

        return [
            ['part' => 'flavor', 'title' => $this->settingText('cards', 'flavor'), 'rows' => $flavors],
            ['part' => 'variant', 'title' => $this->settingText('cards', 'variant'), 'rows' => [
                $row($item(null, $this->settingText('variants', 'production'), false)),
                $row($item('-dev', $this->settingText('variants', 'dev'), true)),
            ]],
            ['part' => 'version', 'title' => $this->settingText('cards', 'version'), 'rows' => $versions],
            ['part' => 'base', 'title' => $this->settingText('cards', 'base'), 'rows' => $bases],
        ];
    }

    /**
     * A row of items.
     *
     * @param Item ...$items
     * @return Row
     */
    private function row(array ...$items): array
    {
        return ['items' => array_values($items), 'note' => null];
    }

    /**
     * The base OS options: tags without a suffix first, then the suffixes in the order of
     * conf/readme-banner.yml. Suffixes listed there that are not built are left out.
     *
     * @return list<array{suffix: string, text: string, notes: list<string>}>
     */
    private function bases(): array
    {
        if ($this->bases !== null) {
            return $this->bases;
        }

        $built = $this->matrix->baseSuffixes();
        $texts = $this->texts['bases']['suffixes'] ?? [];
        foreach ($built as $suffix) {
            if ($suffix !== ImageMatrix::NO_SUFFIX && !isset($texts[$suffix])) {
                throw new RuntimeException(sprintf(
                    'The README banner has no description for the tag suffix "-%s". Add one under "bases: suffixes" in conf/readme-banner.yml.',
                    $suffix,
                ));
            }
        }

        $bases = [];
        if (in_array(ImageMatrix::NO_SUFFIX, $built, true)) {
            $bases[] = ['suffix' => ImageMatrix::NO_SUFFIX, ...$this->baseText(ImageMatrix::NO_SUFFIX, $this->settingText('bases', 'default'))];
        }

        foreach ($texts as $suffix => $text) {
            if (in_array((string)$suffix, $built, true)) {
                $bases[] = ['suffix' => (string)$suffix, ...$this->baseText((string)$suffix, $text)];
            }
        }

        return $this->bases = $bases;
    }

    /**
     * Replaces {release} with the release in the FROM line of webdevops/php, e.g. bookworm.
     * If the PHP versions use different releases, {release} is left out and each release
     * gets a note with its PHP versions, e.g. "bookworm: 8.2–8.5" and "trixie: 8.6".
     *
     * @return array{text: string, notes: list<string>}
     */
    private function baseText(string $suffix, string $text): array
    {
        if (!str_contains($text, '{release}')) {
            return ['text' => $text, 'notes' => []];
        }

        // Versions that follow each other with the same release become a range, e.g. 8.2–8.5
        $ranges = [];
        $previous = null;
        foreach ($this->matrix->releases($suffix) as $version => $release) {
            if ($release === $previous) {
                $ranges[$release][array_key_last($ranges[$release])][1] = (string)$version;
            } else {
                $ranges[$release][] = [(string)$version, (string)$version];
            }

            $previous = $release;
        }

        if (count($ranges) === 1) {
            return ['text' => str_replace('{release}', array_key_first($ranges), $text), 'notes' => []];
        }

        $notes = [];
        foreach ($ranges as $release => $releaseRanges) {
            $versions = implode(', ', array_map(fn (array $r): string => $r[0] === $r[1] ? $r[0] : $r[0] . '–' . $r[1], $releaseRanges));
            $notes[] = str_replace(['{release}', '{versions}'], [(string)$release, $versions], $this->settingText('bases', 'releases'));
        }

        return ['text' => trim((string)preg_replace('/\s+/', ' ', str_replace('{release}', '', $text))), 'notes' => $notes];
    }

    /**
     * A setting from conf/readme-banner.yml, e.g. setting('bases', 'releases').
     */
    private function setting(string ...$path): mixed
    {
        $value = $this->texts;
        foreach ($path as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                throw new RuntimeException(sprintf('conf/readme-banner.yml has no setting "%s".', implode(': ', $path)));
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * A text setting from conf/readme-banner.yml, e.g. settingText('cards', 'flavor').
     */
    private function settingText(string ...$path): string
    {
        $value = $this->setting(...$path);
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('The setting "%s" in conf/readme-banner.yml must be a text.', implode(': ', $path)));
        }

        return $value;
    }

    /**
     * The example tag uses -dev, the base OS suffix from conf/readme-banner.yml
     * and the newest PHP version that is built with both.
     *
     * @return array{flavor: string, version: string, base: string}
     */
    private function example(): array
    {
        if ($this->example !== null) {
            return $this->example;
        }

        $flavor = $this->setting('example', 'flavor');
        $base = (string)$this->setting('example', 'base');
        if (!in_array($flavor, $this->matrix->flavors(), true)) {
            throw new RuntimeException(sprintf(
                'The README banner example uses "%s", which is not built. Change "example" in conf/readme-banner.yml.',
                $flavor,
            ));
        }

        $version = $base === ImageMatrix::NO_SUFFIX ? null : $this->matrix->newestVersion($flavor . '-dev', $base);
        if ($version === null) {
            throw new RuntimeException(sprintf(
                'The README banner example needs webdevops/%s-dev images with the tag suffix "-%s". Change "example" in conf/readme-banner.yml.',
                $flavor,
                $base,
            ));
        }

        return $this->example = ['flavor' => $flavor, 'version' => $version, 'base' => $base];
    }

    /**
     * @return positive-int
     */
    private function versionChipsPerRow(): int
    {
        $inner = (self::WIDTH - 2 * self::MARGIN - 3 * self::CARD_GAP) / 4 - 2 * self::CARD_PADDING;
        $widest = max(0.0, ...array_map(fn (string $v): float => $this->chipWidth($v, true), $this->matrix->versions()));
        return max(1, (int)(($inner + 8) / ($widest + 8)));
    }

    /**
     * Draws the example tag and returns the horizontal center of each coloured part.
     *
     * @param list<string> $svg
     * @param array<string, string> $colors
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
            ['-' . $example['base'], 'base'],
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
                    Svg::number($x + 4),
                    self::BAR_Y,
                    Svg::number($width - 8),
                    $color,
                );
            }

            $x += $width;
        }

        return $centers;
    }

    /**
     * @param list<string> $svg
     */
    private function connector(array &$svg, float $from, float $to, string $color): void
    {
        $top = self::BAR_Y + 14;
        $bottom = self::CARD_Y - 6;
        $middle = ($top + $bottom) / 2;
        $svg[] = sprintf(
            '<path d="M%1$s %2$d C%1$s %3$s %4$s %3$s %4$s %5$d" fill="none" stroke="%6$s" stroke-width="2.5" stroke-linecap="round" opacity="0.75"/>',
            Svg::number($from),
            $top,
            Svg::number($middle),
            Svg::number($to),
            $bottom,
            $color,
        );
        $svg[] = sprintf('<circle cx="%s" cy="%d" r="4" fill="%s"/>', Svg::number($from), $top, $color);
        $svg[] = sprintf('<circle cx="%s" cy="%d" r="4" fill="%s"/>', Svg::number($to), $bottom, $color);
    }

    /**
     * Draws a chip with an image name part; null draws a dashed "no suffix" chip.
     *
     * @param list<string> $svg
     * @param array<string, string> $colors
     * @return float the width of the chip
     */
    private function chip(array &$svg, array $colors, string $color, float $x, float $y, ?string $text, bool $selected, bool $compact): float
    {
        $width = $this->chipWidth($text, $compact);
        if ($selected) {
            $svg[] = sprintf(
                '<rect x="%s" y="%s" width="%s" height="%d" rx="8" fill="%s"/>',
                Svg::number($x),
                Svg::number($y),
                Svg::number($width),
                self::CHIP_HEIGHT,
                $color,
            );
        } else {
            $svg[] = sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="8" fill="none" stroke="%s" stroke-width="1.5"%s/>',
                Svg::number($x + .75),
                Svg::number($y + .75),
                Svg::number($width - 1.5),
                Svg::number(self::CHIP_HEIGHT - 1.5),
                $colors['chipBorder'],
                $text === null ? ' stroke-dasharray="4 3"' : '',
            );
        }

        if ($text === null) {
            $svg[] = $this->text('sans-medium', self::NO_SUFFIX_LABEL, $x + $width / 2, $y + self::CHIP_HEIGHT / 2 + 5.5, 15, $colors['muted'], 0, 'middle');
        } else {
            $svg[] = $this->text('mono-medium', $text, $x + $width / 2, $y + self::CHIP_HEIGHT / 2 + 6.8, self::CHIP_SIZE, $selected ? $colors['onAccent'] : $colors['muted'], 0, 'middle');
        }

        return $width;
    }

    private function chipWidth(?string $text, bool $compact): float
    {
        if ($text === null) {
            return $this->fonts['sans-medium']->width(self::NO_SUFFIX_LABEL, 15) + 20;
        }

        return $this->fonts['mono-medium']->width($text, self::CHIP_SIZE) + ($compact ? 14 : 20);
    }

    /**
     * @param list<string> $svg
     * @param array<string, string> $colors
     */
    private function footer(array &$svg, array $colors, int $y): void
    {
        $platforms = implode(' + ', $this->matrix->platforms());
        $parts = [];
        foreach ($this->setting('footer') as $i => $line) {
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
                $colors['muted'],
                $isSeparator ? ' opacity="0.55"' : '',
                $font->path($text, $x, $y, 18),
            );
            $x += $font->width($text, 18);
        }
    }

    /**
     * @param 'start'|'middle'|'end' $anchor
     */
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
                $what,
                ceil($width),
                floor($available),
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
        $bases = array_map(
            fn (array $b): string => sprintf(
                '%s for %s%s',
                $b['suffix'] === ImageMatrix::NO_SUFFIX ? self::NO_SUFFIX_LABEL : '-' . $b['suffix'],
                $b['text'],
                $b['notes'] === [] ? '' : ' (' . implode('; ', $b['notes']) . ')',
            ),
            $this->bases(),
        );
        $footer = str_replace('{platforms}', implode(' + ', $this->matrix->platforms()), implode('. ', $this->setting('footer')));
        return sprintf(
            'Image tag anatomy using webdevops/%s-dev:%s-%s as the example. '
            . 'Web server: %s. Variant: no suffix for %s, -dev for %s. PHP version: %s. '
            . 'Base OS: %s. %s.',
            $example['flavor'],
            $example['version'],
            $example['base'],
            $this->list($flavors),
            $this->setting('variants', 'production'),
            $this->setting('variants', 'dev'),
            $this->list($versions),
            implode(', ', $bases),
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
