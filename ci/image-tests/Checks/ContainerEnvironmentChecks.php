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
     * Hooks/ and is there before cron starts. Only in the images that start cron.
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
