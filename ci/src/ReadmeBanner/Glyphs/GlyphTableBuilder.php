<?php

namespace Webdevops\Build\ReadmeBanner\Glyphs;

use RuntimeException;
use Webdevops\Build\ReadmeBanner\GlyphFont;

use function array_diff;
use function array_keys;
use function array_map;
use function count;
use function ctype_alpha;
use function explode;
use function implode;
use function json_encode;
use function mb_chr;
use function mb_ord;
use function mb_str_split;
use function sprintf;
use function strcmp;
use function usort;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Builds ci/resources/readme-banner/glyphs.json: outline, advance width and
 * kerning of every character the README banner can use, per font.
 */
final class GlyphTableBuilder
{
    /**
     * Font families by the first part of the font key, e.g. "mono" in "mono-bold".
     */
    public const array SOURCES = [
        'mono' => 'JetBrains Mono, SIL Open Font License 1.1, see OFL-JetBrainsMono.txt',
        'sans' => 'Inter, SIL Open Font License 1.1, see OFL-Inter.txt',
    ];

    private const string EXTRA_CHARACTERS = '·–—…‘’“”';

    /**
     * Printable ASCII and a few typographic characters.
     *
     * @return list<string>
     */
    public static function characters(): array
    {
        $characters = [];
        for ($codePoint = 0x20; $codePoint < 0x7F; $codePoint++) {
            $characters[] = mb_chr($codePoint);
        }

        return [...$characters, ...mb_str_split(self::EXTRA_CHARACTERS)];
    }

    /**
     * @param array<string, string> $fontFiles font file per font key, e.g. "mono-bold" => "JetBrainsMono-Bold.ttf"
     */
    public function build(array $fontFiles): string
    {
        $missing = array_diff(GlyphFont::FONTS, array_keys($fontFiles));
        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'The README banner draws with the fonts %s. Add %s.',
                implode(', ', GlyphFont::FONTS),
                implode(', ', array_map(fn (string $key): string => $key . '=<file>', $missing)),
            ));
        }

        $fonts = [];
        foreach ($fontFiles as $key => $file) {
            $family = explode('-', $key)[0];
            if (!isset(self::SOURCES[$family])) {
                throw new RuntimeException(sprintf(
                    'The font key "%s" has to start with %s.',
                    $key,
                    implode(' or ', array_map(fn (string $f): string => '"' . $f . '-"', array_keys(self::SOURCES))),
                ));
            }

            $fonts[$key] = $this->buildFont(new TrueTypeFont($file), self::SOURCES[$family]);
        }

        return $this->dump($fonts);
    }

    /**
     * @return array{source: string, unitsPerEm: int, glyphs: list<array{0: string, 1: int, 2: string}>, kerning: list<array{0: string, 1: int}>}
     */
    private function buildFont(TrueTypeFont $font, string $source): array
    {
        $glyphIds = [];
        $glyphs = [];
        foreach (self::characters() as $character) {
            $glyph = $font->glyphId(mb_ord($character));
            if ($glyph === null) {
                throw new RuntimeException(sprintf('%s has no glyph for "%s".', $font->path(), $character));
            }

            $glyphIds[$character] = $glyph;
            $glyphs[] = [$character, $font->advanceWidth($glyph), $font->outline($glyph)];
        }

        $kerning = [];
        foreach (self::characters() as $first) {
            foreach (self::characters() as $second) {
                $latin = ctype_alpha($first) || ctype_alpha($second);
                $kern = $font->kerning($glyphIds[$first], $glyphIds[$second], $latin);
                if ($kern !== 0) {
                    $kerning[] = [$first . $second, $kern];
                }
            }
        }

        usort($kerning, fn (array $a, array $b): int => strcmp($a[0], $b[0]));

        return ['source' => $source, 'unitsPerEm' => $font->unitsPerEm(), 'glyphs' => $glyphs, 'kerning' => $kerning];
    }

    /**
     * JSON with one glyph per line, so changes stay readable in a diff.
     *
     * @param array<string, array{source: string, unitsPerEm: int, glyphs: list<array{0: string, 1: int, 2: string}>, kerning: list<array{0: string, 1: int}>}> $fonts
     */
    private function dump(array $fonts): string
    {
        $lines = ['{'];
        $i = 0;
        foreach ($fonts as $key => $font) {
            $lines[] = '  ' . $this->json((string)$key) . ': {';
            $lines[] = '    "source": ' . $this->json($font['source']) . ',';
            $lines[] = '    "unitsPerEm": ' . $font['unitsPerEm'] . ',';
            $lines[] = '    "glyphs": {';
            $lines[] = implode(",\n", array_map(
                fn (array $glyph): string => sprintf('      %s: [%d, %s]', $this->json($glyph[0]), $glyph[1], $this->json($glyph[2])),
                $font['glyphs'],
            ));
            $lines[] = '    },';
            $lines[] = '    "kerning": {' . implode(', ', array_map(
                fn (array $pair): string => $this->json($pair[0]) . ': ' . $pair[1],
                $font['kerning'],
            )) . '}';
            $lines[] = '  }' . (++$i < count($fonts) ? ',' : '');
        }

        $lines[] = '}';
        return implode("\n", $lines) . "\n";
    }

    private function json(string $text): string
    {
        return json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
