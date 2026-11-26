<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;

/**
 * Checks of the images with Nginx: configuration layout, TLS files, and Nginx answering on port 80 and 443.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait NginxChecks
{
    use PiPageChecks;
    use TlsCertificateChecks;

    /**
     * @return array<string, array{string}>
     */
    public static function nginxDirectories(): array
    {
        return self::cases([
            '/opt/docker/etc/nginx',
            '/opt/docker/etc/nginx/conf.d',
            '/opt/docker/etc/nginx/vhost.common.d',
            '/opt/docker/etc/nginx/vhost.ipv6',
            '/opt/docker/bin/service.d/nginx.d',
        ]);
    }

    #[DataProvider('nginxDirectories')]
    public function testNginxDirectory(string $path): void
    {
        $this->assertRootDirectory($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nginxFiles(): array
    {
        return self::cases([
            '/opt/docker/etc/nginx/global.conf',
            '/opt/docker/etc/nginx/main.conf',
            '/opt/docker/etc/nginx/php.conf',
            '/opt/docker/etc/nginx/vhost.conf',
            '/opt/docker/etc/nginx/vhost.ssl.conf',
            '/opt/docker/etc/nginx/vhost.common.d/10-location-root.conf',
        ]);
    }

    #[DataProvider('nginxFiles')]
    public function testNginxFile(string $path): void
    {
        $this->assertRootFile($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nginxScripts(): array
    {
        return self::cases([
            '/opt/docker/bin/service.d/nginx.sh',
            '/opt/docker/bin/service.d/nginx.d/10-init.sh',
        ]);
    }

    #[DataProvider('nginxScripts')]
    public function testNginxScript(string $path): void
    {
        $this->assertRootScript($path);
    }

    public function testDefaultVhostIncludesTheIpv6ListenDirectives(): void
    {
        $vhost = $this->stdout('cat /opt/docker/etc/nginx/vhost.conf');
        self::assertStringContainsString('include /opt/docker/etc/nginx/vhost.ipv6/listen-80*.conf;', $vhost);
        self::assertStringContainsString('include /opt/docker/etc/nginx/vhost.ipv6/listen-443*.conf;', $vhost);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function ipv6ListenDirectives(): iterable
    {
        yield 'listen-80.conf' => ['/opt/docker/etc/nginx/vhost.ipv6/listen-80.conf', 'listen [::]:80 default_server;'];
        yield 'listen-443.conf' => ['/opt/docker/etc/nginx/vhost.ipv6/listen-443.conf', 'listen [::]:443 ssl default_server;'];
    }

    /**
     * The start script deletes the IPv6 listen directives if the kernel has no IPv6, so it checks the same condition
     * in the container.
     */
    #[DataProvider('ipv6ListenDirectives')]
    public function testIpv6ListenDirective(string $path, string $directive): void
    {
        if ($this->execute('test -e /proc/net/if_inet6')->exitCode !== 0) {
            self::assertFalse($this->file($path)->exists, $path . ' should be deleted without IPv6');
            return;
        }

        $file = $this->file($path);
        self::assertTrue($file->isFile, $path . ' should be a file');
        self::assertStringContainsString($directive, $this->stdout('cat ' . escapeshellarg($path)));
        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, ['owner' => 'rX', 'group' => 'rX', 'others' => 'rX']);
    }

    public function testNginxTlsDirectoryIsPrivate(): void
    {
        $this->assertPrivateToRoot('/opt/docker/etc/nginx/ssl', true);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nginxTlsFiles(): array
    {
        return self::cases([
            '/opt/docker/etc/nginx/ssl/server.crt',
            '/opt/docker/etc/nginx/ssl/server.csr',
            '/opt/docker/etc/nginx/ssl/server.key',
        ]);
    }

    /**
     * The image creates its own certificate for *.vm at the build, valid for ten years.
     */
    public function testNginxTlsCertificateIsValid(): void
    {
        $this->assertTlsCertificateIsValid('/opt/docker/etc/nginx/ssl');
    }

    #[DataProvider('nginxTlsFiles')]
    public function testNginxTlsFileIsPrivate(string $path): void
    {
        $this->assertPrivateToRoot($path, false);
    }

    public function testNginxListens(): void
    {
        $this->assertListening(80);
    }

    public function testNginxRuns(): void
    {
        $this->assertServiceRunning('nginx');
    }

    /**
     * Without LOG_STDOUT and LOG_STDERR, in the container of the class, Nginx writes its access log to the stdout of the
     * container and its errors to the stderr: a file it may not read gets 403 and an error.
     */
    public function testNginxLogsToTheContainerLogByDefault(): void
    {
        $container = self::container();
        $file = '/app/image-test-forbidden-' . bin2hex(random_bytes(4)) . '.html';
        $this->assertCommandSucceeds('touch ' . $file . ' && chmod 000 ' . $file, $container);
        self::assertSame('403', $this->statusCode('http://localhost' . substr($file, 4), container: $container));
        $request = '#"GET ' . preg_quote(substr($file, 4), '#') . ' HTTP/1\.1" 403 #';
        $error = '#\[error\] .*' . preg_quote(basename($file), '#') . '" failed \(13: Permission denied\)#';
        $this->eventually(function () use ($container, $request, $error): void {
            [$stdout, $stderr] = $container->logStreams();
            self::assertMatchesRegularExpression($request, $stdout);
            self::assertMatchesRegularExpression($error, $stderr);
            self::assertDoesNotMatchRegularExpression($request, $stderr);
            self::assertDoesNotMatchRegularExpression($error, $stdout);
        });
    }

    /**
     * Without LOG_STDERR, Nginx writes the messages that belong to no server, those of its main error log, to the stderr
     * of the container, in Debian as in Alpine: the master process logs that a worker process ended on SIGKILL.
     */
    public function testNginxMainErrorLogToTheContainerStderrByDefault(): void
    {
        $container = self::container();
        // wait for Nginx: / of the empty /app answers 403
        $this->eventually(function () use ($container): void {
            self::assertNotSame('000', $this->statusCode('http://localhost/', container: $container));
        });
        $this->assertCommandSucceeds(
            'for process in /proc/[0-9]*; do if tr "\\000" " " < $process/cmdline 2>/dev/null | grep -q "^nginx: worker process"; then kill -9 ${process#/proc/}; break; fi; done',
            $container,
        );
        $exited = '#\[alert\] .*worker process \d+ exited on signal 9#';
        $this->eventually(function () use ($container, $exited): void {
            self::assertMatchesRegularExpression($exited, $container->logStreams()[1]);
        });
        self::assertDoesNotMatchRegularExpression($exited, $container->logStreams()[0]);
    }
}
