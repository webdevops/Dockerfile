<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webdevops\Build\DockerHub\PageGenerator;

/**
 * Writes the Docker Hub pages (docs/dockerhub/*.md) from conf/dockerhub.yml and the Dockerfiles.
 */
#[AsCommand(name: 'generate:dockerhub', description: 'Writes the Docker Hub pages (docs/dockerhub/*.md) from conf/dockerhub.yml and the Dockerfiles')]
class GenerateDockerhubCommand extends Command
{
    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../../..')
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = new PageGenerator($this->rootPath)->generate();
        foreach ($result['written'] as $file) {
            $output->writeln('Wrote ' . $file);
        }

        foreach ($result['removed'] as $file) {
            $output->writeln('Removed ' . $file);
        }

        return Command::SUCCESS;
    }
}
