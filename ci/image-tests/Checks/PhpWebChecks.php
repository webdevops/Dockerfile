<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function json_decode;
use function preg_quote;

use const JSON_THROW_ON_ERROR;

/**
 * Requests through the web server to PHP-FPM, with Fixtures/app/php-test.php.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpWebChecks
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function phpSchemes(): iterable
    {
        yield 'http' => ['http'];
        yield 'https' => ['https'];
    }

    /**
     * A multipart upload works and the session keeps its data, although PHP-FPM disables the upload progress.
     */
    #[DataProvider('phpSchemes')]
    public function testUploadWithoutUploadProgress(string $scheme): void
    {
        $body = $this->request(
            $scheme . '://localhost/php-test.php?test=session_upload_progress',
            '--form PHP_SESSION_UPLOAD_PROGRESS=image-test --form file=@/app/pi-number.html',
        );
        self::assertSame(
            ['upload_progress_enabled' => false, 'file_uploaded' => true, 'session_persisted' => true],
            json_decode($body, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testPhpFpmModules(): void
    {
        $modules = $this->phpFpmModules();
        foreach ([...PhpModules::fpm(self::image()), 'shmop', 'Zend OPcache', 'redis'] as $module) {
            self::assertContains($module, $modules, 'PHP-FPM should load ' . $module);
        }
    }

    #[DataProvider('phpSchemes')]
    public function testPhpFpmComputesSha1(string $scheme): void
    {
        $body = $this->phpTestPage($scheme, 'sha1');
        self::assertStringContainsString('2ae62521966cf6d4188acefc943c903e5fc0a25c', $body);
    }

    #[DataProvider('phpSchemes')]
    public function testPhpFpmScansTheImageIniFiles(string $scheme): void
    {
        $body = $this->phpTestPage($scheme, 'php_ini_scanned_files');
        self::assertStringContainsString('-docker.ini', $body);
        self::assertStringContainsString('-webdevops.ini', $body);
    }

    #[DataProvider('phpSchemes')]
    public function testPhpFpmSapi(string $scheme): void
    {
        self::assertStringContainsString('fpm-fcgi', $this->phpTestPage($scheme, 'php_sapi_name'));
    }

    #[DataProvider('phpSchemes')]
    public function testPhpFpmRunsAsTheApplicationUser(string $scheme): void
    {
        self::assertStringContainsString('UID:1000#', $this->phpTestPage($scheme, 'process_user_id'));
    }

    #[DataProvider('phpSchemes')]
    public function testPhpFpmVersion(string $scheme): void
    {
        self::assertMatchesRegularExpression(
            '/PHP ' . preg_quote(self::image()->phpMinorVersion(), '/') . '\.[0-9]+(-[^\(]*)?/',
            $this->phpTestPage($scheme, 'version'),
        );
    }

    /**
     * The extensions that PHP-FPM loads, from get_loaded_extensions().
     *
     * @return list<string>
     */
    private function phpFpmModules(): array
    {
        return $this->lines($this->request('http://localhost/php-test.php?test=get_loaded_extensions'));
    }

    /**
     * The answer of php-test.php?test=$test, which must not contain a PHP notice, warning or fatal error.
     */
    private function phpTestPage(string $scheme, string $test): string
    {
        $body = $this->request($scheme . '://localhost/php-test.php?test=' . $test);
        $this->assertNoPhpErrors($body);
        return $body;
    }
}
