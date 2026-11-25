<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * syslog-ng leaves the owner and mode of the container log to the container runtime and to the entrypoint. Only in
 * webdevops/php: the images built on it have the same syslog-ng configuration and entrypoint, see AGENTS.md.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait SyslogChecks
{
    private const string OWNER_AND_MODE = "stat -L -c '%a %U:%G' ";

    /**
     * Without LOG_STDOUT, in the container of the class, /docker.stdout is the stdout of the container, a pipe of the
     * container runtime like /docker.stderr. syslog-ng writes to it but keeps its owner and mode: on gVisor before
     * release-20261005.0, as on DigitalOcean App Platform, fchmod() on it drops its file type, and no service can open
     * it afterwards (google/gvisor@3cf8bfde6).
     */
    public function testSyslogKeepsTheOwnerAndModeOfTheContainerStdout(): void
    {
        $container = self::container();
        // once its message is in the container log, syslog-ng has opened /docker.stdout
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test stdout-marker', $container);
            self::assertStringContainsString('image-test: stdout-marker', $container->logStreams()[0]);
        });
        self::assertSame(
            $this->stdout("stat -L -c '%f %U:%G' /docker.stderr", $container),
            $this->stdout("stat -L -c '%f %U:%G' /docker.stdout", $container),
            'The stdout of the container should keep the type, mode and owner that it has like its stderr.',
        );
    }

    /**
     * The files of LOG_STDOUT and LOG_STDERR, which do not exist when the environment container starts, are created as
     * root:adm 0640 before the services start, whichever service writes to them first.
     */
    public function testLogFilesFromTheEnvironmentAreCreatedAsRootAdm0640(): void
    {
        $container = $this->environmentContainer();
        // once syslog-ng wrote to it, every service that writes there has opened the file
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test log-file-marker', $container);
            self::assertStringContainsString('image-test: log-file-marker', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
        });
        self::assertSame("640 root:adm\n", $this->stdout(self::OWNER_AND_MODE . static::ENVIRONMENT['LOG_STDOUT'], $container));
        self::assertSame("640 root:adm\n", $this->stdout(self::OWNER_AND_MODE . static::ENVIRONMENT['LOG_STDERR'], $container));
    }
}
