<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;
use function in_array;

/**
 * Checks of the images based on webdevops/base: the layout under /opt/docker, supervisor and the tools.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait BaseChecks
{
    /** The environment of cron for a job, without MAILTO */
    private const string CRONJOB_SHELL_ENV = 'env -i PATH=/usr/bin:/bin';

    /** A command for docker-cronjob-shell, as cron runs it: it prints CRONJOB_SHELL_OUTPUT and ends with 3 */
    private const string CRONJOB_SHELL_COMMAND = '/usr/local/bin/docker-cronjob-shell -c "echo image-test-cronjob-shell | tr a-z A-Z; exit 3"';

    private const string CRONJOB_SHELL_OUTPUT = 'IMAGE-TEST-CRONJOB-SHELL';

    /**
     * @return array<string, array{string}>
     */
    public static function baseDirectories(): array
    {
        return self::cases([
            '/opt/',
            '/opt/docker',
            '/opt/docker/bin',
            '/opt/docker/bin/service.d',
            '/opt/docker/etc',
            '/opt/docker/etc/logrotate.d',
            '/opt/docker/etc/supervisor.d',
            '/opt/docker/etc/syslog-ng',
            '/opt/docker/provision/entrypoint.d',
            '/opt/docker/provision/bootstrap.d',
        ]);
    }

    #[DataProvider('baseDirectories')]
    public function testBaseDirectory(string $path): void
    {
        $this->assertRootDirectory($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function baseFiles(): array
    {
        return self::cases([
            '/opt/docker/etc/supervisor.conf',
            '/opt/docker/etc/logrotate.d/syslog-ng',
            '/opt/docker/etc/supervisor.d/cron.conf',
            '/opt/docker/etc/supervisor.d/syslog.conf',
            '/opt/docker/etc/supervisor.d/dnsmasq.conf',
            '/opt/docker/etc/supervisor.d/postfix.conf',
            '/opt/docker/etc/supervisor.d/ssh.conf',
            '/opt/docker/etc/syslog-ng/syslog-ng.conf',
        ]);
    }

    #[DataProvider('baseFiles')]
    public function testBaseFile(string $path): void
    {
        $this->assertRootFile($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function baseScripts(): array
    {
        return self::cases([
            '/opt/docker/bin/bootstrap.sh',
            '/opt/docker/bin/config.sh',
            '/opt/docker/bin/control.sh',
            '/opt/docker/bin/entrypoint.sh',
            '/opt/docker/bin/service.d/syslog-ng.sh',
            '/opt/docker/bin/service.d/supervisor.sh',
            '/opt/docker/bin/entrypoint.d/cli.sh',
            '/opt/docker/bin/entrypoint.d/default.sh',
            '/opt/docker/bin/entrypoint.d/noop.sh',
            '/opt/docker/bin/entrypoint.d/root.sh',
            '/opt/docker/bin/entrypoint.d/supervisord.sh',
            '/opt/docker/bin/service.d/dnsmasq.sh',
            '/opt/docker/bin/service.d/postfix.sh',
            '/usr/local/bin/service',
            '/usr/local/bin/docker-service-enable',
            '/usr/local/bin/docker-service-disable',
        ]);
    }

    #[DataProvider('baseScripts')]
    public function testBaseScript(string $path): void
    {
        $this->assertRootScript($path);
    }

    /**
     * Cron jobs of the distribution that the images remove.
     *
     * @return array<string, array{string}>
     */
    public static function removedCronJobs(): array
    {
        return self::cases([
            '/etc/cron.daily/logrotate',
            '/etc/cron.daily/apt-compat',
            '/etc/cron.daily/dpkg',
            '/etc/cron.daily/passwd',
            '/etc/cron.daily/0yum-daily.cron',
            '/etc/cron.hourly/0yum-hourly.cron',
            '/etc/periodic/daily/logrotate',
        ]);
    }

    #[DataProvider('removedCronJobs')]
    public function testCronJobIsRemoved(string $path): void
    {
        self::assertFalse($this->file($path)->isFile, $path . ' should not be a file');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function basePackages(): array
    {
        return self::cases([
            'wget',
            'curl',
            'supervisor',
        ]);
    }

    #[DataProvider('basePackages')]
    public function testBasePackageIsInstalled(string $package): void
    {
        $this->assertPackageInstalled($package);
    }

    /**
     * With MAILTO, which a crontab file sets for its jobs, docker-cronjob-shell only runs the command: its output stays
     * with cron, which mails it as before, see docs/customization.md#cron-jobs. The exit code stays that of the command.
     */
    public function testDockerCronjobShellLeavesTheOutputToCronWithMailto(): void
    {
        $result = $this->execute(self::CRONJOB_SHELL_ENV . ' MAILTO=root ' . self::CRONJOB_SHELL_COMMAND);
        self::assertSame([3, self::CRONJOB_SHELL_OUTPUT . "\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    /**
     * Without MAILTO, docker-cronjob-shell sends the output of the command to syslog, which syslog-ng writes to the
     * container log; in the images without syslog-ng, the output stays with cron. The exit code stays that of the
     * command.
     */
    public function testDockerCronjobShellSendsTheOutputToSyslog(): void
    {
        $result = $this->execute(self::CRONJOB_SHELL_ENV . ' ' . self::CRONJOB_SHELL_COMMAND);
        if (!in_array('syslog', self::services(), true)) {
            self::assertSame([3, self::CRONJOB_SHELL_OUTPUT . "\n"], [$result->exitCode, $result->stdout], $result->describe());
            return;
        }

        self::assertSame([3, ''], [$result->exitCode, $result->stdout], $result->describe());
        $this->eventually(function (): void {
            self::assertMatchesRegularExpression('/ cron-job\[\d+\]: ' . self::CRONJOB_SHELL_OUTPUT . '$/m', self::container()->logs());
        });
    }

    /**
     * docker-cronjob adds a job in the system crontab format, with its user, see docs/customization.md#cron-jobs, and
     * cron runs it as that user, also in Alpine, whose BusyBox crond reads no /etc/cron.d. In a container of its own,
     * whose cron starts after the job was added, as in an image built with the job; images that do not start cron
     * install it first.
     */
    public function testDockerCronjobAddsAJobThatCronRunsAsItsUser(): void
    {
        $container = $this->startTestContainer('cronjob');
        $job = 'docker-cronjob ' . escapeshellarg('* * * * * nobody id -un > /tmp/image-test-cronjob');
        $this->assertCommandSucceeds(
            in_array('cron', self::services(), true)
                ? $job . ' && docker-service restart cron'
                : 'docker-service enable cron && ' . $job . ' && docker-service start cron',
            $container,
        );
        // cron runs the job at the start of the next minute
        $this->waitFor(function () use ($container): void {
            self::assertSame("nobody\n", $this->execute('cat /tmp/image-test-cronjob 2>&1', $container)->stdout);
        }, 30, 3);
    }
}
