<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function abs;
use function end;
use function escapeshellarg;
use function in_array;
use function preg_match_all;
use function sprintf;
use function strtoupper;
use function substr_count;
use function time;
use function trim;

/**
 * TZ, LOG_STDOUT and LOG_STDERR in the environment container, see docs/environment-variables.md#container and
 * docs/customization.md#time-zone. What the web servers write to the log files is checked by NginxEnvironmentChecks
 * and ApacheEnvironmentChecks. The CRONJOB_<NAME> variables in containers of their own, see
 * docs/customization.md#cron-jobs.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ContainerEnvironmentChecks
{
    private const string NO_CRON = 'The image does not start cron.';

    /**
     * The command of the job of CRONJOB_IMAGETEST in testCronjobVariablesAreWrittenAnewAtEveryStart(), and that of a
     * job that docker-cronjob adds there, as in an image built with it.
     */
    private const string CRONJOB_VARIABLE_COMMAND = 'echo image-test-variable';

    private const string CRONJOB_BUILD_COMMAND = 'echo image-test-build';

    /**
     * The value of IMAGE_TEST_VALUE in testCronJobsGetTheVariablesOfTheContainer(), with spaces, quotes and a dollar
     * sign, and the start of the names of the files its jobs write.
     */
    private const string CRON_ENVIRONMENT_VALUE = 'a b "c" $d';

    private const string CRON_ENVIRONMENT_FILE = '/tmp/image-test-cron-environment-';

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

    /**
     * CRONJOB_<NAME>: cron runs the job of the variable as its user, and its output reaches the container log: on
     * Alpine from crond through supervisord, on Debian through docker-cronjob-shell and syslog-ng, where it runs. The
     * job writes the line in upper case, so it differs from its command, which cron logs.
     */
    public function testCronRunsTheJobOfACronjobVariableAsItsUser(): void
    {
        $container = $this->startTestContainer('cronjob-variable', [
            'CRONJOB_IMAGETEST' => '* * * * * nobody id -un > /tmp/image-test-cronjob-variable; echo image-test-cronjob-output | tr a-z A-Z',
        ]);
        // cron runs the job at the start of the next minute
        $this->waitFor(function () use ($container): void {
            self::assertSame("nobody\n", $this->execute('cat /tmp/image-test-cronjob-variable 2>&1', $container)->stdout);
        }, 30, 3);
        if (self::image()->isAlpine() || in_array('syslog', self::services(), true)) {
            $this->waitFor(function () use ($container): void {
                self::assertMatchesRegularExpression('/IMAGE-TEST-CRONJOB-OUTPUT$/m', $container->logs());
            }, 5);
        }
    }

    /**
     * CRONJOB_<NAME>: the entrypoint writes the jobs of the variables anew at every start. After a restart the job is
     * there once, next to a job that docker-cronjob added; without the variable it is gone, the other job stays. Cron
     * switched off with docker-service disable cron is switched on again at the start, for the job.
     */
    public function testCronjobVariablesAreWrittenAnewAtEveryStart(): void
    {
        $container = $this->startTestContainer('cronjob-restart', [
            'CRONJOB_IMAGETEST' => '* * * * * nobody ' . self::CRONJOB_VARIABLE_COMMAND,
        ]);
        $this->assertCommandSucceeds('docker-cronjob ' . escapeshellarg('* * * * * nobody ' . self::CRONJOB_BUILD_COMMAND), $container);
        $this->assertCommandSucceeds('docker-service disable cron', $container);
        // Debian: a file for the variables, one for docker-cronjob; Alpine: the crontab of the user
        $crontabs = self::image()->isAlpine()
            ? '/etc/crontabs/nobody'
            : '/etc/cron.d/webdevops-environment /etc/cron.d/webdevops-docker';

        $container->restart();
        self::assertStringContainsString('Enabling cron for the jobs of the CRONJOB_* variables', $container->logs());
        $this->waitFor(function () use ($container): void {
            $result = $this->execute('docker-service check cron', $container);
            self::assertStringContainsString('ok', $result->stdout, $result->describe());
            self::assertSame(0, $result->exitCode, $result->describe());
        }, 5);

        $jobs = $this->stdout('cat ' . $crontabs, $container);
        self::assertSame(1, substr_count($jobs, self::CRONJOB_VARIABLE_COMMAND), $jobs);
        self::assertSame(1, substr_count($jobs, self::CRONJOB_BUILD_COMMAND), $jobs);

        // as at a start without the variable
        $this->assertCommandSucceeds('env -u CRONJOB_IMAGETEST docker-cronjob --environment', $container);
        $jobs = $this->execute('cat ' . $crontabs . ' 2>/dev/null', $container)->stdout;
        self::assertSame(0, substr_count($jobs, self::CRONJOB_VARIABLE_COMMAND), $jobs);
        self::assertSame(1, substr_count($jobs, self::CRONJOB_BUILD_COMMAND), $jobs);
    }

    /**
     * Cron jobs get the variables of the container, with their values as set, and its PATH: the jobs of CRONJOB_<NAME>
     * as nobody and, where the image has the user, as application, and a job that docker-cronjob adds, as in an image
     * built with it. Cron in Debian passes none of them on by itself.
     */
    public function testCronJobsGetTheVariablesOfTheContainer(): void
    {
        $users = $this->execute('id -u application')->exitCode === 0 ? ['nobody', 'application'] : ['nobody'];
        $environment = ['IMAGE_TEST_VALUE' => self::CRON_ENVIRONMENT_VALUE];
        foreach ($users as $user) {
            $environment['CRONJOB_IMAGETEST_' . strtoupper($user)] = '* * * * * ' . $user . ' ' . self::cronEnvironmentCommand($user);
        }

        $container = $this->startTestContainer('cronjob-environment', $environment);
        // cron starts again with the job, as in an image built with it
        $this->assertCommandSucceeds('docker-cronjob ' . escapeshellarg('* * * * * nobody ' . self::cronEnvironmentCommand('docker-cronjob')) . ' && docker-service restart cron', $container);
        $expected = self::CRON_ENVIRONMENT_VALUE . "\n" . $this->stdout('printenv PATH', $container);
        foreach ([...$users, 'docker-cronjob'] as $name) {
            // cron runs the jobs at the start of the next minute
            $this->waitFor(function () use ($container, $name, $expected): void {
                self::assertSame($expected, $this->execute('cat ' . self::CRON_ENVIRONMENT_FILE . $name . ' 2>&1', $container)->stdout, 'the job of ' . $name);
            }, 30, 3);
        }
    }

    /**
     * The command of a job that writes the value of IMAGE_TEST_VALUE and its PATH into the file of $name.
     */
    private static function cronEnvironmentCommand(string $name): string
    {
        return sprintf('printenv IMAGE_TEST_VALUE > %1$s; echo "$PATH" >> %1$s', self::CRON_ENVIRONMENT_FILE . $name);
    }

    /**
     * Values of CRONJOB_<NAME> that are not a job cron would run: not a job at all, a user that does not exist, a time
     * out of range, an unknown keyword.
     *
     * @return array<string, array{string}>
     */
    public static function invalidCronjobVariables(): array
    {
        return self::cases([
            'not a job',
            '* * * * * image-test-nobody true',
            '0 24 * * * nobody true',
            '@dayly nobody true',
        ]);
    }

    /**
     * CRONJOB_<NAME> with a value that is not a job: the entrypoint fails with an error that names the variable, and
     * the command does not run. With a command that exits, so that the test does not wait for supervisord where the
     * variable has no effect.
     */
    #[DataProvider('invalidCronjobVariables')]
    public function testInvalidCronjobVariableStopsTheContainer(string $value): void
    {
        $result = $this->runContainer(['root', 'echo', 'image-test-started'], ['CRONJOB_BROKEN' => $value]);
        self::assertSame(1, $result->exitCode, $result->describe());
        self::assertStringContainsString('[ERROR] CRONJOB_BROKEN: ', $result->stderr, $result->describe());
        self::assertStringNotContainsString('image-test-started', $result->stdout, $result->describe());
    }
}
