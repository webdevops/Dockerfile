<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use Closure;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Webdevops\Build\ImageTests;

use function is_string;
use function preg_match;
use function sprintf;

use const PHP_BINARY;

/**
 * Runs the image tests of one webdevops image: PHPUnit starts a container of the image and checks it.
 */
#[AsCommand(name: 'test:image', description: 'Runs the image tests of webdevops/<image>:<tag> against a container of it')]
class TestImageCommand extends Command
{
    /**
     * @var Closure(list<string>, array<string, string>, OutputInterface): int
     */
    private readonly Closure $runner;

    /**
     * @param string $rootPath the repository root
     * @param (Closure(list<string>, array<string, string>, OutputInterface): int)|null $runner runs PHPUnit, by default as a process
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../../..', ?Closure $runner = null)
    {
        parent::__construct();
        $this->runner = $runner ?? $this->runProcess(...);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('image', InputArgument::REQUIRED, 'The image whose tests run, e.g. webdevops/php-nginx:8.5')
            ->addOption('reference', 'r', InputOption::VALUE_REQUIRED, 'The image to start, e.g. a local build or ghcr.io/webdevops/php-nginx:sha-<commit>-amd64-8.5; by default the image itself');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('image');
        if (!is_string($name) || !preg_match('#^webdevops/([a-z0-9-]+):([A-Za-z0-9._-]+)$#', $name, $matches)) {
            throw new InvalidArgumentException('Name the image as webdevops/<image>:<tag>, e.g. webdevops/php-nginx:8.5.');
        }

        $imageTests = new ImageTests($this->rootPath);
        $testFile = $imageTests->testFile($matches[1]);
        if ($testFile === null) {
            throw new InvalidArgumentException(sprintf('webdevops/%s has no image tests in %s.', $matches[1], ImageTests::DIRECTORY));
        }

        $reference = $input->getOption('reference');
        $environment = [
            ImageTests::ENVIRONMENT_PREFIX . 'NAME' => $name,
            ImageTests::ENVIRONMENT_PREFIX . 'REFERENCE' => is_string($reference) && $reference !== '' ? $reference : $name,
        ];
        foreach ($imageTests->settings($name) as $key => $value) {
            $environment[ImageTests::ENVIRONMENT_PREFIX . $key] = $value;
        }

        return ($this->runner)(
            [PHP_BINARY, $this->rootPath . '/ci/vendor/bin/phpunit', '--configuration', $this->rootPath . '/ci/phpunit.xml.dist', $this->rootPath . '/' . $testFile],
            $environment,
            $output,
        );
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     */
    private function runProcess(array $command, array $environment, OutputInterface $output): int
    {
        $process = new Process($command, null, $environment, null, null);
        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
    }
}
