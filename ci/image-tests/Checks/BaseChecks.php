<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;
use function in_array;
use function trim;

/**
 * Checks of the images based on webdevops/base: the layout under /opt/docker, supervisor and the tools.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait BaseChecks
{
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

    /**
     * The commands of webdevops/base, where the distribution has them: netstat and sed are in /bin in Alpine.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function baseCommands(): iterable
    {
        $commands = [
            'supervisord' => '#^/usr/bin/supervisord$#',
            'wget' => '#^/usr/bin/wget$#',
            'curl' => '#^/usr/bin/curl$#',
            'zic' => '#^/usr/sbin/zic$#',
            'netstat' => '#^/(usr/)?bin/netstat$#',
            'sed' => '#^/(usr/)?bin/sed$#',
        ];
        foreach ($commands as $command => $path) {
            yield $command => [$command, $path];
        }
    }

    #[DataProvider('baseCommands')]
    public function testBaseCommandIsOnThePath(string $command, string $path): void
    {
        self::assertMatchesRegularExpression($path, trim($this->stdout('command -v ' . escapeshellarg($command))));
    }

    /**
     * The scripts and the configuration of webdevops/base have exactly the modes 0755 and 0644: no setuid, setgid or
     * sticky bit.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function basePermissions(): iterable
    {
        $scripts = [
            '/opt/docker/bin/bootstrap.sh',
            '/opt/docker/bin/config.sh',
            '/opt/docker/bin/control.sh',
            '/opt/docker/bin/entrypoint.sh',
            '/opt/docker/bin/service.d/supervisor.sh',
        ];
        foreach ($scripts as $path) {
            yield $path => [$path, '-rwxr-xr-x'];
        }

        $configuration = [
            '/opt/docker/etc/supervisor.conf',
            '/opt/docker/etc/supervisor.d/cron.conf',
            '/opt/docker/etc/supervisor.d/dnsmasq.conf',
            '/opt/docker/etc/supervisor.d/postfix.conf',
            '/opt/docker/etc/supervisor.d/ssh.conf',
            '/opt/docker/etc/supervisor.d/syslog.conf',
            '/opt/docker/etc/logrotate.d/syslog-ng',
            '/opt/docker/etc/syslog-ng/syslog-ng.conf',
        ];
        foreach ($configuration as $path) {
            yield $path => [$path, '-rw-r--r--'];
        }
    }

    #[DataProvider('basePermissions')]
    public function testBasePermissions(string $path, string $permissions): void
    {
        self::assertSame($permissions, $this->file($path)->permissions(), $path);
    }
}
