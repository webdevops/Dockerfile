<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webdevops\Build\Dockerfile\DockerfileGenerator;

/**
 * Renders docker/<image>/<tag>/Dockerfile from the templates.
 */
#[AsCommand(name: 'generate:dockerfile', description: 'Renders the Dockerfiles under docker/ from the Dockerfile.twig next to them')]
class GenerateDockerfileCommand extends Command
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
        new DockerfileGenerator($this->rootPath)->generate($output->writeln(...));
        return Command::SUCCESS;
    }
}
