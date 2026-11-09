<?php

namespace Webdevops\Build\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webdevops\Build\ReadmeBanner\Glyphs\GlyphTableBuilder;

use function dirname;
use function explode;
use function file_put_contents;
use function sprintf;
use function str_contains;

/**
 * Writes the glyph table the README banner draws its texts with.
 * Only needed to change a font or to add characters; see ci/resources/readme-banner/README.md.
 */
class BuildGlyphsCommand extends Command
{
    protected static $defaultName = 'readme-banner:build-glyphs';

    protected function configure(): void
    {
        $this
            ->setDescription('Writes the glyph table of the README banner from TrueType font files')
            ->addArgument(
                'fonts',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Font key and .ttf file, e.g. mono-bold=JetBrainsMono-Bold.ttf',
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'File to write',
                dirname(__DIR__, 3) . '/ci/resources/readme-banner/glyphs.json',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fonts = [];
        foreach ($input->getArgument('fonts') as $argument) {
            if (!str_contains($argument, '=')) {
                $output->writeln(sprintf('<error>"%s" is not of the form key=file, e.g. mono-bold=JetBrainsMono-Bold.ttf</error>', $argument));
                return Command::INVALID;
            }

            [$key, $file] = explode('=', $argument, 2);
            $fonts[$key] = $file;
        }

        $file = $input->getOption('output');
        if (file_put_contents($file, new GlyphTableBuilder()->build($fonts)) === false) {
            $output->writeln(sprintf('<error>Cannot write %s</error>', $file));
            return Command::FAILURE;
        }

        $output->writeln('Wrote ' . $file);
        return Command::SUCCESS;
    }
}
