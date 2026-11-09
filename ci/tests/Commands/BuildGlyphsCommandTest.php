<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\BuildGlyphsCommand;
use Webdevops\Build\ReadmeBanner\GlyphFont;
use Webdevops\Build\ReadmeBanner\Glyphs\GlyphTableBuilder;
use Webdevops\Build\Tests\ReadmeBanner\Glyphs\FontBuilder;

use function array_keys;
use function array_map;
use function file_get_contents;
use function json_decode;
use function mb_ord;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;

#[CoversClass(BuildGlyphsCommand::class)]
final class BuildGlyphsCommandTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testWritesTheGlyphTable(): void
    {
        $output = $this->files[] = (string)tempnam(sys_get_temp_dir(), 'glyphs-');
        $font = $this->font();
        $tester = new CommandTester(new BuildGlyphsCommand());

        $status = $tester->execute([
            'fonts' => array_map(fn (string $key): string => $key . '=' . $font, GlyphFont::FONTS),
            '--output' => $output,
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame('Wrote ' . $output . "\n", $tester->getDisplay());
        self::assertSame(GlyphFont::FONTS, array_keys(json_decode((string)file_get_contents($output), true, 512, JSON_THROW_ON_ERROR)));
    }

    public function testFontsAreKeyAndFile(): void
    {
        $tester = new CommandTester(new BuildGlyphsCommand());

        self::assertSame(Command::INVALID, $tester->execute(['fonts' => ['mono-bold=a.ttf', 'JetBrainsMono-Medium.ttf']]));
        self::assertSame("\"JetBrainsMono-Medium.ttf\" is not of the form key=file, e.g. mono-bold=JetBrainsMono-Bold.ttf\n", $tester->getDisplay());
    }

    public function testWritesTheGlyphTableOfTheBannerByDefault(): void
    {
        $default = new BuildGlyphsCommand()->getDefinition()->getOption('output')->getDefault();

        self::assertIsString($default);
        self::assertSame(realpath(__DIR__ . '/../../resources/readme-banner/glyphs.json'), realpath($default));
    }

    /**
     * A font with an empty glyph for every character of the banner.
     */
    private function font(): string
    {
        $builder = new FontBuilder();
        $glyph = $builder->emptyGlyph(500);
        foreach (GlyphTableBuilder::characters() as $character) {
            $builder->map(mb_ord($character), $glyph);
        }

        return $this->files[] = $builder->save();
    }
}
