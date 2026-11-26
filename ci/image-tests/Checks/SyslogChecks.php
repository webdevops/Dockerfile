<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * syslog-ng writes errors to /docker.stderr and the other messages to /docker.stdout, and leaves the owner and mode of
 * the container log to the container runtime and to the entrypoint. Only in webdevops/php: the images built on it have
 * the same syslog-ng configuration and entrypoint, see AGENTS.md.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait SyslogChecks
{
    private const string OWNER_AND_MODE = "stat -L -c '%a %U:%G' ";

    /**
     * Without LOG_STDOUT and LOG_STDERR, in the container of the class, /docker.stdout and /docker.stderr are the stdout
     * and stderr of the container, pipes of the container runtime. syslog-ng writes to both but keeps their owner and
     * mode: on gVisor before
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
        // and once its error is in the container log, syslog-ng has opened /docker.stderr
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test -p user.err stderr-marker', $container);
            self::assertStringContainsString('image-test: stderr-marker', $container->logStreams()[1]);
        });
        self::assertSame("fifo\n", $this->stdout('stat -L -c %F /docker.stdout', $container), 'The stdout of the container should stay a pipe.');
        self::assertSame("fifo\n", $this->stdout('stat -L -c %F /docker.stderr', $container), 'The stderr of the container should stay a pipe.');
        self::assertSame(
            $this->stdout("stat -L -c '%f %U:%G' /docker.stderr", $container),
            $this->stdout("stat -L -c '%f %U:%G' /docker.stdout", $container),
            'The stdout of the container should keep the type, mode and owner that it has like its stderr.',
        );
    }

    /**
     * The files of LOG_STDOUT and LOG_STDERR, which do not exist when the environment container starts, are created as
     * root:root 0644 before the services start, whichever service writes to them first.
     */
    public function testLogFilesFromTheEnvironmentAreCreatedAsRootRoot0644(): void
    {
        $container = $this->environmentContainer();
        // once syslog-ng wrote to it, every service that writes there has opened the file
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test log-file-marker', $container);
            self::assertStringContainsString('image-test: log-file-marker', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
        });
        self::assertSame("644 root:root\n", $this->stdout(self::OWNER_AND_MODE . static::ENVIRONMENT['LOG_STDOUT'], $container));
        self::assertSame("644 root:root\n", $this->stdout(self::OWNER_AND_MODE . static::ENVIRONMENT['LOG_STDERR'], $container));
    }

    /**
     * When the file of LOG_STDOUT cannot be created, here in /proc, the container starts all the same and the log names
     * the file; the entrypoint, which stops at an error, must not stop at it.
     */
    public function testContainerStartsWhenTheFileOfLogStdoutCannotBeCreated(): void
    {
        $file = '/proc/image-test-stdout.log';
        $container = $this->startTestContainer('log-file-not-created', ['LOG_STDOUT' => $file]);
        $this->eventually(function () use ($container, $file): void {
            self::assertStringContainsString('Cannot create ' . $file . ' for LOG_STDOUT or LOG_STDERR', $container->logs());
        });
        // cron, which does not write to LOG_STDOUT when it starts, runs
        $this->eventually(function () use ($container): void {
            self::assertStringContainsString('RUNNING', $this->stdout('supervisorctl status cron:crond', $container));
        });
    }

    /**
     * syslog-ng writes the messages up to the level warning to the stdout of the container and those from the level err
     * to its stderr, in the container of the class, without LOG_STDOUT and LOG_STDERR.
     */
    public function testSyslogWritesErrorsToTheContainerStderrAndTheRestToItsStdout(): void
    {
        $container = self::container();
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test -p user.warning severity-warning-marker', $container);
            $this->assertCommandSucceeds('logger -t image-test -p user.err severity-err-marker', $container);
            [$stdout, $stderr] = $container->logStreams();
            self::assertStringContainsString('image-test: severity-warning-marker', $stdout);
            self::assertStringContainsString('image-test: severity-err-marker', $stderr);
            self::assertStringNotContainsString('severity-warning-marker', $stderr);
            self::assertStringNotContainsString('severity-err-marker', $stdout);
        });
    }

    /**
     * With LOG_STDOUT and LOG_STDERR, syslog-ng writes the same split to their files, which it opens as files, not as
     * pipes.
     */
    public function testSyslogWritesErrorsToTheFileOfLogStderrAndTheRestToTheFileOfLogStdout(): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test -p user.info file-severity-info-marker', $container);
            $this->assertCommandSucceeds('logger -t image-test -p user.crit file-severity-crit-marker', $container);
            $stdout = $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container);
            $stderr = $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container);
            self::assertStringContainsString('image-test: file-severity-info-marker', $stdout);
            self::assertStringContainsString('image-test: file-severity-crit-marker', $stderr);
            self::assertStringNotContainsString('file-severity-info-marker', $stderr);
            self::assertStringNotContainsString('file-severity-crit-marker', $stdout);
        });
    }
}
