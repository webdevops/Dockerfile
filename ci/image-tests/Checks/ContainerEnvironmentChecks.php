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
    private const string NO_CRON = 'The image does not start cron.';

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
     * Hooks/ and is there before cron starts; the test works out its hours, see ImageTestCase::cronHours(). Only in the
     * images that start cron.
     */
    public function testCronRunsJobsInTheTimeZoneFromTheEnvironment(): void
    {
        if (!in_array('cron', self::services(), true)) {
            self::markTestSkipped(self::NO_CRON);
        }

        $container = $this->environmentContainer();
        // cron runs jobs at the start of a minute
        $this->waitFor(function () use ($container): void {
            self::assertSame("ran\n", $this->stdout('cat /tmp/image-tests/cron-job 2>&1 || true', $container));
        }, 25, 3);
    }

    /**
     * SERVICE_CRON_OPTS: cron logs the job of the environment container at the log level of the variable, through
     * syslog-ng into the file of LOG_STDOUT. At the default level, Debian logs only "CMD (echo ran ...)" and BusyBox
     * only "USER root pid ... cmd echo ran ...", so the lines that the test looks for come from the variable: the end of
     * the job with its process id (-L 15), the steps of BusyBox (-l 0). Only in the images that start cron.
     */
    public function testCronLogsAtTheLevelFromTheEnvironment(): void
    {
        if (!in_array('cron', self::services(), true)) {
            self::markTestSkipped(self::NO_CRON);
        }

        $container = $this->environmentContainer();
        // cron runs the job at the start of a minute, in the hours of ImageTestCase::cronHours()
        $this->waitFor(function () use ($container): void {
            self::assertMatchesRegularExpression(
                self::image()->isAlpine() ? '/crond\[\d+\]: +job: \d+ echo ran/' : '/CRON\[\d+\]: \(root\) END \(\[\d+\] echo ran/',
                $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container),
                'SERVICE_CRON_OPTS=' . self::cronOptions(),
            );
        }, 25, 3);
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
     * The output of a cron job that docker-cronjob added, from Hooks/, reaches the file of LOG_STDOUT in Debian and
     * Ubuntu. Cron there would mail it, or discard it without a mail server: docker-cronjob-shell passes both lines to
     * syslog-ng, which writes them to the file of LOG_STDOUT. Only in the Debian and Ubuntu images that start cron;
     * BusyBox crond on Alpine passes the output on itself.
     */
    public function testCronJobOutputReachesTheFileFromTheEnvironment(): void
    {
        if (!in_array('cron', self::services(), true)) {
            self::markTestSkipped(self::NO_CRON);
        }
        if (self::image()->isAlpine()) {
            self::markTestSkipped('BusyBox crond passes the output of a job on itself.');
        }

        $container = $this->environmentContainer();
        // the job writes the lines in upper case, so they differ from its command, which cron logs
        $this->waitFor(function () use ($container): void {
            $stdout = $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container);
            self::assertMatchesRegularExpression('/ cron-job\[\d+\]: IMAGE-TEST-CRON-OUTPUT$/m', $stdout);
            self::assertMatchesRegularExpression('/ cron-job\[\d+\]: IMAGE-TEST-CRON-ERROR$/m', $stdout);
        }, 25, 3);
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
