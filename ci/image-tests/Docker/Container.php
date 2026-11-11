<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use RuntimeException;
use Symfony\Component\Process\Process;

use function rtrim;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * A running container of the image under test, controlled with the docker command.
 */
final readonly class Container
{
    /**
     * Seconds a single command in the container may take.
     */
    private const int COMMAND_TIMEOUT = 300;

    private function __construct(public string $id)
    {
    }

    /**
     * Creates a container of $image with the image's own command, copies the content of the directory $files into its
     * root directory and starts it. stdin stays open, so that an image whose command is a shell, such as
     * webdevops/bootstrap, keeps running.
     */
    public static function start(string $image, string $files): self
    {
        $container = new self(trim(self::docker(['create', '--interactive', $image])));
        try {
            self::docker(['cp', rtrim($files, '/') . '/.', $container->id . ':/']);
            self::docker(['start', $container->id]);
        } catch (RuntimeException $runtimeException) {
            $container->remove();
            throw $runtimeException;
        }

        return $container;
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
        if (str_starts_with($stderr, 'Error response from daemon')) {
            // the container is gone or stopped, so the command did not run at all
            throw new RuntimeException(sprintf('docker exec %s failed: %s', $this->id, trim($stderr)));
        }

        return new CommandResult($command, $process->getOutput(), $stderr, (int)$process->getExitCode());
    }

    /**
     * Stops and removes the container.
     */
    public function remove(): void
    {
        new Process(['docker', 'rm', '--force', '--volumes', $this->id])->run();
    }

    /**
     * @param list<string> $arguments
     */
    private static function docker(array $arguments): string
    {
        $process = new Process(['docker', ...$arguments]);
        $process->setTimeout(self::COMMAND_TIMEOUT);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf('docker %s failed: %s', $arguments[0], trim($process->getErrorOutput())));
        }

        return $process->getOutput();
    }
}
