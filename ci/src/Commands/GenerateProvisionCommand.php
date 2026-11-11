<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webdevops\Build\Provision\Provisioner;

/**
 * Copies the files of provisioning/ into docker/<image>/<tag>/conf/, as conf/provision.yml says.
 */
#[AsCommand(name: 'generate:provision', description: 'Copies provisioning/ into the conf/ directories under docker/, as conf/provision.yml says')]
class GenerateProvisionCommand extends Command
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
        new Provisioner($this->rootPath)->provision($output->writeln(...));
        return Command::SUCCESS;
    }
}
