<?php

declare(strict_types=1);

namespace Webdevops\Build\Commands;

use Closure;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Webdevops\Build\ImageTests;

use function bin2hex;
use function defined;
use function is_string;
use function preg_match;
use function preg_split;
use function random_bytes;
use function sprintf;
use function trim;
use function usleep;

use const PHP_BINARY;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Runs the image tests of one webdevops image: PHPUnit starts a container of the image and checks it. The container
 * carries a label with the run, and the command removes it afterwards, also when PHPUnit dies or the run is
 * interrupted. On an interruption it stops PHPUnit first, so that PHPUnit starts no further container, and removes
 * the containers twice, a second apart, for a container that a docker command under way still creates.
 */
#[AsCommand(name: 'test:image', description: 'Runs the image tests of webdevops/<image>:<tag> against a container of it')]
class TestImageCommand extends Command implements SignalableCommandInterface
{
    /**
     * Microseconds between the two removals after an interruption.
     */
    private const int SECOND_REMOVAL_DELAY = 1_000_000;

    /**
     * @var Closure(list<string>, array<string, string>, OutputInterface): int
     */
    private readonly Closure $runner;

    /**
     * @var Closure(string): void
     */
    private readonly Closure $removeContainers;

    /**
     * @var Closure(): void
     */
    private readonly Closure $stopRunner;

    /**
     * PHPUnit while the default runner runs it.
     */
    private ?Process $process = null;

    /**
     * The run in progress, the value of the container label.
     */
    private ?string $run = null;

    /**
     * @param string $rootPath the repository root
     * @param (Closure(list<string>, array<string, string>, OutputInterface): int)|null $runner runs PHPUnit, by default as a process
     * @param (Closure(string): void)|null $removeContainers removes the containers of a run, by default with docker
     * @param (Closure(): void)|null $stopRunner stops PHPUnit on an interruption, by default the process of the runner
     */
    public function __construct(
        private readonly string $rootPath = __DIR__ . '/../../..',
        ?Closure $runner = null,
        ?Closure $removeContainers = null,
        ?Closure $stopRunner = null,
    ) {
        parent::__construct();
        $this->runner = $runner ?? $this->runProcess(...);
        $this->removeContainers = $removeContainers ?? $this->removeLabelledContainers(...);
        $this->stopRunner = $stopRunner ?? $this->stopProcess(...);
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

        $this->run = bin2hex(random_bytes(8));
        $reference = $input->getOption('reference');
        $environment = [
            ImageTests::ENVIRONMENT_PREFIX . 'NAME' => $name,
            ImageTests::ENVIRONMENT_PREFIX . 'REFERENCE' => is_string($reference) && $reference !== '' ? $reference : $name,
            ImageTests::ENVIRONMENT_PREFIX . 'RUN' => $this->run,
        ];
        foreach ($imageTests->settings($name) as $key => $value) {
            $environment[ImageTests::ENVIRONMENT_PREFIX . $key] = $value;
        }

        try {
            return ($this->runner)(
                [PHP_BINARY, $this->rootPath . '/ci/vendor/bin/phpunit', '--configuration', $this->rootPath . '/ci/phpunit.xml.dist', $this->rootPath . '/' . $testFile],
                $environment,
                $output,
            );
        } finally {
            // unless a signal removed them already
            if ($this->run !== null) {
                ($this->removeContainers)($this->run);
                $this->run = null;
            }
        }
    }

    /**
     * @return list<int>
     */
    public function getSubscribedSignals(): array
    {
        return defined('SIGINT') && defined('SIGTERM') ? [SIGINT, SIGTERM] : [];
    }

    /**
     * On Ctrl-C or a stop, removes the containers of the run and exits.
     */
    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        ($this->stopRunner)();
        $run = $this->run;
        if ($run !== null) {
            $this->run = null;
            ($this->removeContainers)($run);
            // a docker command that PHPUnit had under way may still create its container
            usleep(self::SECOND_REMOVAL_DELAY);
            ($this->removeContainers)($run);
        }

        return 128 + $signal;
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     */
    private function runProcess(array $command, array $environment, OutputInterface $output): int
    {
        $this->process = new Process($command, null, $environment, null, null);
        try {
            return $this->process->run(static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            });
        } finally {
            $this->process = null;
        }
    }

    /**
     * Stops PHPUnit: SIGTERM, and SIGKILL if it still runs after 10 s.
     */
    private function stopProcess(): void
    {
        $this->process?->stop(10);
    }

    private function removeLabelledContainers(string $run): void
    {
        $list = new Process(['docker', 'ps', '--all', '--quiet', '--filter', 'label=' . ImageTests::CONTAINER_LABEL . '=' . $run]);
        $list->run();

        $ids = preg_split('/\s+/', trim($list->getOutput()), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($ids !== []) {
            new Process(['docker', 'rm', '--force', '--volumes', ...$ids])->run();
        }
    }
}
