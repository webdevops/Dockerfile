<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use RuntimeException;
use Symfony\Component\Process\Process;

use function fclose;
use function fopen;
use function implode;
use function microtime;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function trim;
use function usleep;

/**
 * A container of the image under test, controlled with the docker command.
 */
final readonly class Container
{
    /**
     * Seconds a single command in the container may take.
     */
    private const int COMMAND_TIMEOUT = 300;

    /**
     * Seconds the entrypoint of the image may take for its scripts before it starts the command.
     */
    private const int ENTRYPOINT_TIMEOUT = 120;

    /**
     * Beginnings of the messages with which the docker command, not the command in the container, fails
     */
    private const array DOCKER_ERRORS = [
        'Error response from daemon',
        'Cannot connect to the Docker daemon',
        'permission denied while trying to connect to the Docker daemon',
        'OCI runtime exec failed',
    ];

    private function __construct(public string $id)
    {
    }

    /**
     * Creates a container of $image, copies the files under each of $directories into its root directory and starts
     * it. Only files are copied: directories of the image keep their owner and mode, see FileArchive. stdin stays
     * open, so that an image whose command is a shell, such as webdevops/bootstrap, keeps running. $labels are added
     * to the container, e.g. to find and remove it later.
     *
     * Returns once the entrypoint of the image has run its scripts, which write the configuration from the
     * environment, and has replaced itself with the command, e.g. supervisord.
     *
     * @param list<string> $directories
     * @param array<string, string> $labels
     * @param array<string, string> $environment variables to set in the container
     * @param list<string> $command the command instead of the image's own, e.g. ['noop']
     */
    public static function start(string $image, array $directories, array $labels = [], array $environment = [], array $command = []): self
    {
        $container = self::create(['--interactive'], $image, $directories, $labels, $environment, $command);
        try {
            self::docker(['start', $container->id]);
            $container->waitForEntrypoint();
        } catch (RuntimeException $runtimeException) {
            $container->remove();
            throw $runtimeException;
        }

        return $container;
    }

    /**
     * Creates a container like start(), runs it until its command exits and removes it. Returns the output and the
     * exit code of the command, e.g. of ['root', 'id', '-un'] through the entrypoint of the image.
     *
     * @param list<string> $directories
     * @param array<string, string> $labels
     * @param array<string, string> $environment
     * @param list<string> $command
     */
    public static function run(string $image, array $directories, array $labels, array $environment, array $command): CommandResult
    {
        $container = self::create([], $image, $directories, $labels, $environment, $command);
        try {
            $process = new Process(['docker', 'start', '--attach', $container->id]);
            $process->setTimeout(self::COMMAND_TIMEOUT);
            $process->run();
            self::failOnDockerError('docker start --attach ' . $container->id, $process->getErrorOutput());

            return new CommandResult(implode(' ', $command), $process->getOutput(), $process->getErrorOutput(), (int)$process->getExitCode());
        } finally {
            $container->remove();
        }
    }

    /**
     * Whether the container still runs, i.e. its command has not exited.
     */
    public function isRunning(): bool
    {
        return trim(self::docker(['inspect', '--format', '{{.State.Running}}', $this->id])) === 'true';
    }

    /**
     * Runs $command with sh -c in the container, as the image's user.
     */
    public function exec(string $command): CommandResult
    {
        $process = new Process(['docker', 'exec', $this->id, 'sh', '-c', $command]);
        $process->setTimeout(self::COMMAND_TIMEOUT);
        $process->run();

        $stderr = $process->getErrorOutput();
        self::failOnDockerError('docker exec ' . $this->id, $stderr);

        return new CommandResult($command, $process->getOutput(), $stderr, (int)$process->getExitCode());
    }

    /**
     * Waits while process 1 is still the entrypoint, /opt/docker/bin/entrypoint.sh started as /entrypoint. Images
     * without it, such as webdevops/bootstrap, are ready at once.
     */
    private function waitForEntrypoint(): void
    {
        $deadline = microtime(true) + self::ENTRYPOINT_TIMEOUT;
        while (true) {
            $process1 = trim($this->exec('tr "\\000" " " < /proc/1/cmdline')->stdout);
            if (!str_contains($process1, '/entrypoint')) {
                return;
            }

            if (microtime(true) > $deadline) {
                throw new RuntimeException(sprintf('The entrypoint of container %s did not start its command within %d s: %s', $this->id, self::ENTRYPOINT_TIMEOUT, $process1));
            }

            usleep(200_000);
        }
    }

    /**
     * @param list<string> $options
     * @param list<string> $directories
     * @param array<string, string> $labels
     * @param array<string, string> $environment
     * @param list<string> $command
     */
    private static function create(array $options, string $image, array $directories, array $labels, array $environment, array $command): self
    {
        $arguments = ['create', ...$options];
        foreach ($labels as $key => $value) {
            $arguments[] = '--label';
            $arguments[] = $key . '=' . $value;
        }

        foreach ($environment as $name => $value) {
            $arguments[] = '--env';
            $arguments[] = $name . '=' . $value;
        }

        $container = new self(trim(self::docker([...$arguments, $image, ...$command])));
        try {
            foreach ($directories as $directory) {
                $container->copy(rtrim($directory, '/'));
            }
        } catch (RuntimeException $runtimeException) {
            $container->remove();
            throw $runtimeException;
        }

        return $container;
    }

    /**
     * Copies the files under $directory into the root directory of the container.
     */
    private function copy(string $directory): void
    {
        $archive = FileArchive::create($directory);
        if (!$archive->exists()) {
            return;
        }

        try {
            $input = fopen($archive->path, 'rb');
            if ($input === false) {
                throw new RuntimeException('Cannot read ' . $archive->path);
            }

            try {
                self::docker(['cp', '-', $this->id . ':/'], $input);
            } finally {
                fclose($input);
            }
        } finally {
            $archive->remove();
        }
    }

    /**
     * Stops and removes the container.
     */
    public function remove(): void
    {
        new Process(['docker', 'rm', '--force', '--volumes', $this->id])->run();
    }

    /**
     * Throws if $stderr shows that docker failed, e.g. because the container stopped or the daemon is gone, so that the
     * command in the container did not run.
     */
    private static function failOnDockerError(string $call, string $stderr): void
    {
        foreach (self::DOCKER_ERRORS as $error) {
            if (str_starts_with($stderr, $error)) {
                throw new RuntimeException(sprintf('%s failed: %s', $call, trim($stderr)));
            }
        }
    }

    /**
     * @param list<string> $arguments
     * @param resource|null $input
     */
    private static function docker(array $arguments, mixed $input = null): string
    {
        $process = new Process(['docker', ...$arguments]);
        $process->setTimeout(self::COMMAND_TIMEOUT);
        $process->setInput($input);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf('docker %s failed: %s', $arguments[0], trim($process->getErrorOutput())));
        }

        return $process->getOutput();
    }
}
