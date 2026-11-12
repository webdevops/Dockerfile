<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_combine;
use function array_map;
use function escapeshellarg;
use function json_decode;
use function preg_quote;
use function sprintf;
use function trim;
use function var_export;
use function version_compare;

use const JSON_THROW_ON_ERROR;

/**
 * Checks of every PHP image: PHP CLI, its extensions and settings, Composer and the image tools.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpChecks
{
    /**
     * The security releases of 2026-10-21, https://www.php.net/
     */
    private const array MINIMUM_PHP_VERSIONS = [
        '8.2' => '8.2.35',
        '8.3' => '8.3.36',
        '8.4' => '8.4.27',
        '8.5' => '8.5.12',
    ];

    public function testPhpServiceDirectory(): void
    {
        $this->assertRootDirectory('/opt/docker/bin/service.d/php-fpm.d');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function phpScripts(): array
    {
        return self::cases([
            '/opt/docker/bin/service.d/php-fpm.sh',
            '/opt/docker/bin/service.d/php-fpm.d/10-init.sh',
            '/usr/local/bin/apt-add-repository',
            '/usr/local/bin/apt-update',
            '/usr/local/bin/docker-image-cleanup',
            '/usr/local/bin/generate-locales',
            '/usr/local/bin/generate-dockerimage-info',
            '/usr/local/bin/docker-run-bootstrap',
            '/sbin/gosu',
        ]);
    }

    #[DataProvider('phpScripts')]
    public function testPhpScript(string $path): void
    {
        $this->assertRootScript($path);
    }

    public function testZicIsInstalled(): void
    {
        if (self::image()->osFamily !== 'debian') {
            self::markTestSkipped('zic is only checked in the Debian images.');
        }

        $file = $this->file('/usr/sbin/zic');
        self::assertTrue($file->isFile, '/usr/sbin/zic should be a file');
        self::assertTrue($file->isExecutable, '/usr/sbin/zic should be executable');
    }

    public function testPhpInfoRuns(): void
    {
        $this->assertCommandSucceeds('php -i');
    }

    public function testPhpVersion(): void
    {
        $minor = self::image()->phpMinorVersion();
        self::assertMatchesRegularExpression(
            '/PHP ' . preg_quote($minor, '/') . '\.[0-9]+(RC[0-9]|beta[0-9])?(-[^\(]*)? \(cli\)/',
            $this->stdout('php -v'),
        );

        $minimum = self::MINIMUM_PHP_VERSIONS[$minor] ?? self::fail(sprintf('No minimum version for PHP %s in %s.', $minor, self::class));
        $version = trim($this->stdout("php -r 'echo PHP_VERSION;'"));
        self::assertMatchesRegularExpression('/\A' . preg_quote($minor, '/') . '\.[0-9]+\z/', $version, 'a stable release of PHP ' . $minor);
        self::assertTrue(version_compare($version, $minimum, '>='), sprintf('PHP %s should be %s or newer', $version, $minimum));
    }

    public function testFtpSslIsAvailable(): void
    {
        $this->assertCommandSucceeds("php -r 'exit(function_exists(\"ftp_ssl_connect\") ? 0 : 1);'");
    }

    public function testPhpModules(): void
    {
        $result = $this->assertCommandSucceeds('php -m');
        self::assertSame('', $result->stderr);
        $modules = $this->lines($result->stdout);
        foreach (PhpModules::loaded(self::image()) as $module) {
            self::assertContains($module, $modules, 'php -m should list ' . $module);
        }
    }

    public function testPhpSettings(): void
    {
        self::assertSame([
            'memory_limit' => '512M',
            'post_max_size' => '50M',
            'upload_max_filesize' => '50M',
            'max_input_vars' => '5000',
            'variables_order' => 'GPCS',
            'request_order' => 'GP',
            'date.timezone' => 'UTC',
            'allow_url_fopen' => '1',
            'allow_url_include' => '',
            'session.upload_progress.enabled' => '',
            'expose_php' => '',
            'log_errors' => '1',
            'open_basedir' => '',
            'output_buffering' => '0',
            'short_open_tag' => '',
        ], $this->phpSettings([
            'memory_limit', 'post_max_size', 'upload_max_filesize', 'max_input_vars', 'variables_order', 'request_order',
            'date.timezone', 'allow_url_fopen', 'allow_url_include', 'session.upload_progress.enabled', 'expose_php',
            'log_errors', 'open_basedir', 'output_buffering', 'short_open_tag',
        ]));
    }

    public function testPhpComputesSha1(): void
    {
        $result = $this->assertCommandSucceeds("php -r 'echo sha1(\"webdevops\");'");
        $this->assertNoPhpErrors($result->stdout . $result->stderr);
        self::assertStringContainsString('2ae62521966cf6d4188acefc943c903e5fc0a25c', $result->stdout);
    }

    public function testGdSupportsAvif(): void
    {
        $this->assertCommandSucceeds("php -r 'exit((gd_info()[\"AVIF Support\"] ?? false) ? 0 : 1);'");
    }

    public function testImapLoads(): void
    {
        if (!self::image()->hasImap()) {
            self::markTestSkipped('PHP ' . self::image()->phpMinorVersion() . ' has no imap extension.');
        }

        foreach (['php -m', "php -r 'exit(extension_loaded(\"imap\") ? 0 : 1);'"] as $command) {
            $result = $this->assertCommandSucceeds($command);
            self::assertStringNotContainsString('Unable to load dynamic library', $result->stderr);
            self::assertStringNotContainsString('libc-client.so.2007e', $result->stderr);
        }

        self::assertContains('imap', $this->lines($this->stdout('php -m')));
    }

    public function testPhpScansTheImageIniFiles(): void
    {
        $files = $this->stdout('php -r "echo php_ini_scanned_files();"');
        self::assertStringContainsString('-docker.ini', $files);
        self::assertStringContainsString('-webdevops.ini', $files);
    }

    public function testPhpSapiIsCli(): void
    {
        self::assertStringContainsString('cli', $this->stdout('php -r "echo php_sapi_name();"'));
    }

    public function testComposer(): void
    {
        foreach (['/usr/local/bin/composer', '/usr/local/bin/composer1', '/usr/local/bin/composer2'] as $path) {
            $file = $this->file($path);
            self::assertTrue($file->isFile, $path . ' should be a file');
            self::assertTrue($file->isExecutable, $path . ' should be executable');
        }

        $this->assertCommandSucceeds('/usr/local/bin/composer');
    }

    public function testGraphicsMagick(): void
    {
        self::assertTrue($this->file('/usr/bin/gm')->isExecutable, '/usr/bin/gm should be executable');
        self::assertMatchesRegularExpression('/GraphicsMagick [0-9]+\.[0-9]+/', $this->stdout('/usr/bin/gm -version'));
    }

    public function testImageMagick(): void
    {
        self::assertTrue($this->file('/usr/bin/convert')->isExecutable, '/usr/bin/convert should be executable');
        self::assertMatchesRegularExpression('/Version: ImageMagick/', $this->stdout('/usr/bin/convert --version'));
    }

    public function testGhostscript(): void
    {
        self::assertTrue($this->file('/usr/bin/gs')->isExecutable, '/usr/bin/gs should be executable');
        self::assertMatchesRegularExpression('/[0-9]+\.[0-9]+/', $this->stdout('/usr/bin/gs --version'));
    }

    public function testFileCommand(): void
    {
        self::assertTrue($this->file('/usr/bin/file')->isExecutable, '/usr/bin/file should be executable');
        self::assertMatchesRegularExpression('/^file-5\.[0-9]+/m', $this->stdout('file --version'));
        // The magic database works: it tells a PHP script from a binary
        self::assertSame(
            "text/x-php\napplication/x-pie-executable\n",
            $this->stdout("printf '<?php echo 1;\\n' > /tmp/file-test.php && file --brief --mime-type /tmp/file-test.php /usr/local/bin/php"),
        );
    }

    public function testSyslogNgIsInstalled(): void
    {
        $this->assertPackageInstalled('syslog-ng');
    }

    /**
     * The values of PHP settings as php.ini sets them (get_cfg_var), as strings; empty for Off and for settings that
     * php.ini does not set.
     *
     * @param list<string> $names
     * @return array<string, string>
     */
    private function phpSettings(array $names): array
    {
        $script = sprintf('echo json_encode(array_map(fn ($name) => (string) get_cfg_var($name), %s));', var_export($names, true));
        /** @var list<string> $values */
        $values = json_decode($this->stdout('php -r ' . escapeshellarg($script)), true, 512, JSON_THROW_ON_ERROR);

        return array_combine($names, array_map(strval(...), $values));
    }
}
