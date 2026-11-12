<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use function sprintf;
use function trim;

/**
 * Output and exit code of a command that ran in the container.
 */
final readonly class CommandResult
{
    public function __construct(
        public string $command,
        public string $stdout,
        public string $stderr,
        public int $exitCode,
    ) {
    }

    /**
     * The command and what it returned, for failure messages.
     */
    public function describe(): string
    {
        return sprintf("%s\nexit code: %d\nstdout: %s\nstderr: %s", $this->command, $this->exitCode, trim($this->stdout), trim($this->stderr));
    }
}
