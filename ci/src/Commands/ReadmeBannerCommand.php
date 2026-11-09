<?php

namespace Webdevops\Build\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webdevops\Build\FileReader;
use Webdevops\Build\GithubJobBuilder;
use Webdevops\Build\ReadmeBanner\BannerGenerator;

use function array_column;
use function dirname;

/**
 * Renders the banner at the top of README.md from the images that are built.
 * Its texts are in conf/readme-banner.yml.
 */
class ReadmeBannerCommand extends Command
{
    protected static $defaultName = 'readme-banner:generate';

    protected function configure(): void
    {
        $this->setDescription('Renders the README banner (docs/assets/readme-banner-*.svg) from the Dockerfiles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileReader = new FileReader();
        $images = [];
        foreach ($fileReader->collectDockerfiles() as [$dockerfile]) {
            $info = $fileReader->getInfo($dockerfile);
            $images[] = ['image' => $info['image'], 'tag' => $info['tag'], 'from' => $info['baseImage']];
        }
        $banner = new BannerGenerator(dirname(__DIR__, 3));
        foreach ($banner->generate($images, array_column(GithubJobBuilder::PLATFORMS, 'platform')) as $file) {
            $output->writeln('Wrote ' . $file);
        }
        return Command::SUCCESS;
    }
}
