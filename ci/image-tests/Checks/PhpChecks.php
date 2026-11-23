<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_combine;
use function array_map;
use function escapeshellarg;
use function implode;
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
    private const string COMPOSER = '/usr/local/bin/composer';

    /**
     * The security releases of 2026-11-19, https://www.php.net/
     */
    private const array MINIMUM_PHP_VERSIONS = [
        '8.2' => '8.2.36',
        '8.3' => '8.3.37',
        '8.4' => '8.4.28',
        '8.5' => '8.5.13',
        '8.6' => '8.6.0',
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

    /**
     * The Debian images enable the components contrib and non-free besides main, for packages such as unrar; apt lists
     * the indexes of all three without fetching them.
     */
    public function testAptUsesContribAndNonFree(): void
    {
        if (self::image()->osFamily !== 'debian') {
            self::markTestSkipped('contrib and non-free are Debian components.');
        }

        $uris = $this->stdout('apt-get --print-uris update');
        foreach (['main', 'contrib', 'non-free'] as $component) {
            self::assertMatchesRegularExpression('#/debian/dists/[a-z]+/' . $component . '/binary-#', $uris, $component);
        }
    }

    /**
     * docker-php-setting, in a container of its own: it appends the setting to /opt/docker/etc/php/php.ini, which PHP
     * and PHP-FPM read after the settings from the environment; a value that is not a number is quoted unless --raw.
     */
    public function testDockerPhpSettingChangesASetting(): void
    {
        $result = $this->runContainer(['sh', '-c', implode(' && ', [
            'docker-php-setting memory_limit 384M > /dev/null',
            'docker-php-setting --raw error_reporting E_ERROR > /dev/null',
            "php -r 'echo ini_get(\"memory_limit\"), \" \", ini_get(\"error_reporting\"), PHP_EOL;'",
            'php-fpm -i | grep "^memory_limit "',
        ])], ['PHP_MEMORY_LIMIT' => '256M']);
        self::assertSame([0, "384M 1\nmemory_limit => 384M => 384M\n"], [$result->exitCode, $result->stdout], $result->describe());
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

    /**
     * The official PHP images, and so the images built on them, are thread safe (ZTS) from PHP 8.6 on.
     */
    public function testPhpThreadSafety(): void
    {
        self::assertSame(self::image()->isThreadSafe() ? 'true' : 'false', $this->stdout("php -r 'var_export((bool) PHP_ZTS);'"));
    }

    /**
     * The Debian images load the ionCube Loader up to PHP 8.5; the Alpine images and PHP 8.6 and later have none.
     */
    public function testIonCubeLoaderUpToPhp85OnDebian(): void
    {
        $result = $this->assertCommandSucceeds("php -r 'var_export(extension_loaded(\"ionCube Loader\"));'");
        $this->assertNoPhpErrors($result->stdout . $result->stderr);
        if (!self::image()->hasIonCube()) {
            self::assertSame('false', $result->stdout);
            return;
        }

        self::assertSame('true', $result->stdout);
        self::assertStringContainsString('with the ionCube PHP Loader', $this->stdout('php -v'));
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
        foreach ([self::COMPOSER, '/usr/local/bin/composer1', '/usr/local/bin/composer2'] as $path) {
            $file = $this->file($path);
            self::assertTrue($file->isFile, $path . ' should be a file');
            self::assertTrue($file->isExecutable, $path . ' should be executable');
        }

        $this->assertCommandSucceeds(self::COMPOSER);
    }

    /**
     * phpversion('imagick') is a version number, not the placeholder of the source, which libraries compare.
     */
    public function testImagickReportsItsVersion(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $this->stdout('php -r \'echo phpversion("imagick");\''));
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

    /**
     * PIE is only mounted while the RUN instructions that install extensions run, and they remove its working
     * directory.
     */
    public function testPieIsOnlyThereDuringTheBuild(): void
    {
        foreach (['/usr/local/bin/pie', '/tmp/pie'] as $path) {
            $file = $this->file($path);
            self::assertFalse($file->exists || $file->isSymlink, $path . ' should not exist');
        }
    }

    /**
     * The scripts of the entrypoint that write the PHP and PHP-FPM settings from the environment.
     */
    public function testEntrypointScriptsOfPhp(): void
    {
        foreach (['/opt/docker/provision/entrypoint.d/20-php.sh', '/opt/docker/provision/entrypoint.d/20-php-fpm.sh'] as $path) {
            self::assertTrue($this->file($path)->isFile, $path . ' should be a file');
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function phpCommands(): iterable
    {
        $commands = ['convert' => '/usr/bin/convert', 'gm' => '/usr/bin/gm', 'gs' => '/usr/bin/gs', 'composer' => self::COMPOSER];
        foreach ($commands as $command => $path) {
            yield $command => [$command, $path];
        }
    }

    #[DataProvider('phpCommands')]
    public function testPhpCommandIsOnThePath(string $command, string $path): void
    {
        self::assertSame($path . "\n", $this->stdout('command -v ' . escapeshellarg($command)));
    }
}
