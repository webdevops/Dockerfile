<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Checks of the images with Apache: configuration layout, TLS files, modules, and Apache answering on port 80 and
 * 443.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ApacheChecks
{
    use PiPageChecks;
    use TlsCertificateChecks;

    /**
     * Modules that Apache loads, with the event MPM and PHP-FPM through proxy_fcgi
     */
    private const array APACHE_MODULES = [
        'core_module', 'http_module', 'so_module', 'log_config_module', 'logio_module', 'version_module',
        'actions_module', 'alias_module', 'auth_basic_module', 'authn_file_module', 'authz_host_module',
        'autoindex_module', 'deflate_module', 'dir_module', 'env_module', 'headers_module', 'mime_module',
        'negotiation_module', 'rewrite_module', 'setenvif_module', 'ssl_module', 'status_module', 'expires_module',
        'mpm_event_module', 'proxy_module', 'proxy_fcgi_module',
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function apacheDirectories(): array
    {
        return self::cases([
            '/opt/docker/etc/httpd',
            '/opt/docker/etc/httpd/conf.d',
            '/opt/docker/etc/httpd/vhost.common.d',
            '/opt/docker/bin/service.d/httpd.d',
        ]);
    }

    #[DataProvider('apacheDirectories')]
    public function testApacheDirectory(string $path): void
    {
        $this->assertRootDirectory($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function apacheFiles(): array
    {
        return self::cases([
            '/opt/docker/etc/httpd/global.conf',
            '/opt/docker/etc/httpd/main.conf',
            '/opt/docker/etc/httpd/php.conf',
            '/opt/docker/etc/httpd/vhost.conf',
            '/opt/docker/etc/httpd/vhost.ssl.conf',
            '/opt/docker/etc/httpd/vhost.common.d/01-boilerplate.conf',
        ]);
    }

    #[DataProvider('apacheFiles')]
    public function testApacheFile(string $path): void
    {
        $this->assertRootFile($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function apacheScripts(): array
    {
        return self::cases([
            '/opt/docker/bin/service.d/httpd.sh',
            '/opt/docker/bin/service.d/httpd.d/10-init.sh',
        ]);
    }

    #[DataProvider('apacheScripts')]
    public function testApacheScript(string $path): void
    {
        $this->assertRootScript($path);
    }

    public function testApacheTlsDirectoryIsPrivate(): void
    {
        $this->assertPrivateToRoot('/opt/docker/etc/httpd/ssl', true);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function apacheTlsFiles(): array
    {
        return self::cases([
            '/opt/docker/etc/httpd/ssl/server.crt',
            '/opt/docker/etc/httpd/ssl/server.csr',
            '/opt/docker/etc/httpd/ssl/server.key',
        ]);
    }

    /**
     * The image creates its own certificate for *.vm at the build, valid for ten years.
     */
    public function testApacheTlsCertificateIsValid(): void
    {
        $this->assertTlsCertificateIsValid('/opt/docker/etc/httpd/ssl');
    }

    #[DataProvider('apacheTlsFiles')]
    public function testApacheTlsFileIsPrivate(string $path): void
    {
        $this->assertPrivateToRoot($path, false);
    }

    public function testApacheListens(): void
    {
        $this->assertListening(80);
    }

    public function testApacheModules(): void
    {
        $modules = $this->stdout('apachectl -t -D DUMP_MODULES');
        foreach (self::APACHE_MODULES as $module) {
            self::assertStringContainsString($module, $modules);
        }

        self::assertStringNotContainsString('fastcgi_module', $modules);
    }

    public function testApacheRuns(): void
    {
        $this->assertServiceRunning('apache');
    }
}
