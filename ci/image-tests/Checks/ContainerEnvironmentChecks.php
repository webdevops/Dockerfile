<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use DateTimeImmutable;
use DateTimeZone;
use Webdevops\Build\ImageTests\ImageTestCase;

use function abs;
use function end;
use function in_array;
use function preg_match_all;
use function sprintf;
use function time;
use function trim;

/**
 * TZ, LOG_STDOUT and LOG_STDERR in the environment container, see docs/environment-variables.md#container and
 * docs/customization.md#time-zone. What the web servers write to the log files is checked by NginxEnvironmentChecks
 * and ApacheEnvironmentChecks.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ContainerEnvironmentChecks
{
    /**
     * TZ=Europe/Berlin: date shows the time zone, and supervisord writes local time to the container log.
     */
    public function testTimeZoneFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertContains(trim($this->stdout('date +%Z', $container)), ['CET', 'CEST'], 'TZ=Europe/Berlin');

        self::assertGreaterThan(0, preg_match_all('/^(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d),\d+ INFO /m', $container->logs(), $matches), 'supervisord should log');
        $last = (string) end($matches[1]);
        // read as time in Berlin, the time stamp is at most minutes old; read as UTC, it would be an hour or two off
        $seconds = abs(time() - new DateTimeImmutable($last, new DateTimeZone('Europe/Berlin'))->getTimestamp());
        self::assertLessThan(1800, $seconds, sprintf('The last time stamp of supervisord, %s, should be the time in Berlin.', $last));
    }

    /**
     * TZ=Europe/Berlin: cron runs a job for the current hour in Berlin, which is not the hour in UTC. The job comes from
     * Hooks/ and is there before cron starts; the test works out its hours, see ImageTestCase::cronHours().
     * SERVICE_CRON_OPTS: cron logs the job at its log level, through syslog-ng into the file of LOG_STDOUT. Only in the
     * images that start cron.
     */
    public function testCronRunsJobsInTheTimeZoneFromTheEnvironment(): void
    {
        if (!in_array('cron', self::services(), true)) {
            self::markTestSkipped('The image does not start cron.');
        }

        $container = $this->environmentContainer();
        // cron runs jobs at the start of a minute
        $this->waitFor(function () use ($container): void {
            self::assertSame("ran\n", $this->stdout('cat /tmp/image-tests/cron-job 2>&1 || true', $container));
        }, 25, 3);
        $this->waitFor(function () use ($container): void {
            self::assertMatchesRegularExpression(
                self::image()->isAlpine() ? '/crond\[\d+\]: USER root pid +\d+ cmd echo ran/' : '/CRON\[\d+\]: \(root\) END \(\[\d+\] echo ran/',
                $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container),
                'SERVICE_CRON_OPTS=' . self::cronOptions(),
            );
        }, 10, 1);
    }

    /**
     * syslog-ng writes the messages of the programs in the container into the file of LOG_STDOUT. The environment
     * container starts it where the image does not.
     */
    public function testSyslogMessagesReachTheFileFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        if (!in_array('syslog', self::services(), true)) {
            $this->assertCommandSucceeds('docker-service enable syslog && docker-service start syslog', $container);
        }

        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test syslog-marker', $container);
            self::assertStringContainsString('image-test: syslog-marker', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
        });
    }

    /**
     * /docker.stdout and /docker.stderr, where the services of the image write their logs, point to the files of
     * LOG_STDOUT and LOG_STDERR.
     */
    public function testLogFilesFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertSame(static::ENVIRONMENT['LOG_STDOUT'] . "\n", $this->stdout('readlink /docker.stdout', $container));
        self::assertSame(static::ENVIRONMENT['LOG_STDERR'] . "\n", $this->stdout('readlink /docker.stderr', $container));
    }
}
