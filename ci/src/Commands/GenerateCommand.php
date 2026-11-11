<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * Writes every generated file, in the order they depend on each other: the build.yaml and the banner are read from
 * the Dockerfiles, so those come first.
 */
#[AsCommand(name: 'generate', description: 'Writes every generated file: the Dockerfiles and conf/ under docker/, .github/workflows/build.yaml and the README banner')]
class GenerateCommand extends Command
{
    /**
     * The commands that write the generated files, in the order they run.
     */
    public const array COMMANDS = ['generate:dockerfile', 'generate:provision', 'generate:github-ci', 'generate:readme-banner'];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if ($application === null) {
            throw new RuntimeException('generate runs the other commands and needs the console application.');
        }

        foreach (self::COMMANDS as $name) {
            $output->writeln(sprintf('<info>%s</info>', $name));
            $status = $application->find($name)->run(new ArrayInput([]), $output);
            if ($status !== Command::SUCCESS) {
                return $status;
            }
        }

        return Command::SUCCESS;
    }
}
