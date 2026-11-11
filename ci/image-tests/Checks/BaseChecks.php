<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

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
}
