<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;
use function json_decode;
use function sleep;
use function sprintf;
use function str_replace;
use function strrpos;
use function substr;

/**
 * The PHP variables in the environment container, see docs/environment-variables.md#php. Each test triggers what the
 * setting changes, e.g. the fatal error of the memory limit, in PHP-FPM, which the tests request over FastCGI without
 * a web server, and in the PHP CLI where the setting applies to it too.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpEnvironmentChecks
{
    /**
     * PHP_MEMORY_LIMIT=64M: 16 MiB fit, 100 MiB do not.
     */
    public function testPhpMemoryLimitFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $error = 'Allowed memory size of 67108864 bytes exhausted';
        self::assertStringContainsString('allocated 16777216', $this->phpFpmBody($container, 'test=allocate&mib=16'));
        self::assertStringContainsString($error, $this->phpFpmBody($container, 'test=allocate&mib=100'));
        self::assertStringContainsString($error, $this->execute("php -r 'str_repeat(\"x\", 100 * 1024 * 1024);' 2>&1", $container)->stdout);
    }

    /**
     * PHP_MAX_EXECUTION_TIME=1: PHP-FPM stops a script that runs for 3 s; the PHP CLI has no limit.
     */
    public function testPhpMaxExecutionTimeFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertStringContainsString('Maximum execution time of 1 second exceeded', $this->phpFpmBody($container, 'test=busy&seconds=3'));
        self::assertSame('finished', $this->stdout('cd /app && php -r \'$_GET = ["test" => "busy", "seconds" => "2"]; include "php-test.php";\' 2>&1', $container));
    }

    /**
     * PHP_POST_MAX_SIZE=1M: PHP discards a body of 2 MiB. PHP_UPLOAD_MAX_FILESIZE=512K: an upload of 600 KiB fails with
     * UPLOAD_ERR_INI_SIZE (1), one of 100 KiB arrives.
     */
    public function testPhpPostAndUploadLimitsFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $post = ['REQUEST_METHOD' => 'POST'];
        $body = $this->phpFpmBody($container, 'test=request', $post, "php -n -r 'echo \"field=\", str_repeat(\"a\", 2 * 1024 * 1024);'");
        self::assertStringContainsString('POST Content-Length of 2097158 bytes exceeds the limit of 1048576 bytes', $body);
        self::assertStringEndsWith('{"post":0,"files":[]}', $body);

        $multipart = $post + ['CONTENT_TYPE' => 'multipart/form-data; boundary=image-test'];
        $upload = static fn (int $kib): string => sprintf(
            "php -n -r 'echo \"--image-test\\r\\nContent-Disposition: form-data; name=\\\"file\\\"; filename=\\\"upload.txt\\\"\\r\\nContent-Type: text/plain\\r\\n\\r\\n\", str_repeat(\"a\", %d * 1024), \"\\r\\n--image-test--\\r\\n\";'",
            $kib,
        );
        self::assertSame('{"post":0,"files":{"file":1}}', $this->phpFpmBody($container, 'test=request', $multipart, $upload(600)));
        self::assertSame('{"post":0,"files":{"file":0}}', $this->phpFpmBody($container, 'test=request', $multipart, $upload(100)));
    }

    /**
     * php.max_input_vars=10: of 20 POST variables, PHP keeps no more than the limit allows and warns.
     */
    public function testPhpIniDirectiveFromTheEnvironment(): void
    {
        $body = $this->phpFpmBody(
            $this->environmentContainer(),
            'test=request',
            ['REQUEST_METHOD' => 'POST'],
            "php -n -r 'echo http_build_query(array_fill_keys(array_map(fn (\$i) => \"field\" . \$i, range(1, 20)), \"a\"));'",
        );
        self::assertStringContainsString('Input variables exceeded 10', $body);
        $values = json_decode(substr($body, (int) strrpos($body, '{"post"')), true);
        self::assertIsArray($values, $body);
        self::assertLessThanOrEqual(11, $values['post'], $body);
    }

    /**
     * PHP_DISPLAY_ERRORS=1: a warning shows in the response of PHP-FPM and in the output of the PHP CLI.
     */
    public function testPhpDisplayErrorsFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertMatchesRegularExpression('/Warning.*Undefined variable \$undefinedImageTestVariable/', $this->phpFpmBody($container, 'test=warning'));
        self::assertStringContainsString('Warning: Undefined variable $undefinedImageTestVariable', $this->stdout('php -r \'echo $undefinedImageTestVariable;\' 2>/dev/null', $container));
    }

    /**
     * PHP_DATE_TIMEZONE=Europe/Berlin: date() in PHP-FPM and in the PHP CLI uses the time zone.
     */
    public function testPhpDateTimezoneFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertMatchesRegularExpression('/^Europe\/Berlin CES?T$/', $this->phpFpmBody($container, 'test=date'));
        self::assertMatchesRegularExpression('/^Europe\/Berlin CES?T$/', $this->stdout("php -r 'echo date_default_timezone_get(), \" \", date(\"T\");'", $container));
    }

    /**
     * PHP_OPCACHE_MEMORY_CONSUMPTION=200, PHP_OPCACHE_INTERNED_STRINGS_BUFFER=12 and
     * PHP_OPCACHE_MAX_ACCELERATED_FILES=10000: the sizes OPcache in PHP-FPM reports. OPcache raises the number of
     * files to the next prime number of its own list.
     */
    public function testOpcacheSizesFromTheEnvironment(): void
    {
        self::assertSame(
            ['memory' => 200 * 1024 * 1024, 'interned_strings_buffer' => 12 * 1024 * 1024, 'max_cached_keys' => 16229],
            json_decode($this->phpFpmBody($this->environmentContainer(), 'test=opcache'), true),
        );
    }

    /**
     * PHP_OPCACHE_VALIDATE_TIMESTAMPS=0: PHP-FPM keeps running the cached script after it changed.
     */
    public function testOpcacheValidateTimestampsFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->writeOpcacheScript($container, 'first');
        self::assertSame('first', $this->phpFpmBody($container, '', script: '/tmp/image-tests/opcache/script.php'));
        $this->writeOpcacheScript($container, 'second', 60);
        sleep(3);
        self::assertSame('first', $this->phpFpmBody($container, '', script: '/tmp/image-tests/opcache/script.php'));
    }

    /**
     * PHP_OPCACHE_REVALIDATE_FREQ=6, with PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 in a container of its own: PHP-FPM runs the
     * cached script for up to 6 s after it cached it, then the new one. 3 s after the change it still runs the cached
     * one, which the default of 2 s in the production images would not.
     */
    public function testOpcacheRevalidateFrequencyFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('opcache', ['PHP_OPCACHE_VALIDATE_TIMESTAMPS' => '1', 'PHP_OPCACHE_REVALIDATE_FREQ' => '6']);
        $this->writeOpcacheScript($container, 'first');
        self::assertSame('first', $this->phpFpmBody($container, '', script: '/tmp/image-tests/opcache/script.php'));
        $this->writeOpcacheScript($container, 'second', 60);
        sleep(3);
        self::assertSame('first', $this->phpFpmBody($container, '', script: '/tmp/image-tests/opcache/script.php'), 'within the 6 s');
        sleep(5);
        self::assertSame('second', $this->phpFpmBody($container, '', script: '/tmp/image-tests/opcache/script.php'), 'after the 6 s');
    }

    /**
     * A PHP script that prints $output, changed $age seconds ago, so that OPcache caches it at once: it skips files
     * changed within the last 2 s. Each version gets another time, so that OPcache sees the change.
     */
    private function writeOpcacheScript(Container $container, string $output, int $age = 120): void
    {
        $this->assertCommandSucceeds(sprintf(
            'mkdir -p /tmp/image-tests/opcache && printf %s > /tmp/image-tests/opcache/script.php && php -n -r %s',
            escapeshellarg('<?php echo "' . $output . '";'),
            escapeshellarg(sprintf('touch("/tmp/image-tests/opcache/script.php", time() - %d);', $age)),
        ), $container);
    }

    /**
     * PHP_SENDMAIL_PATH, in a container of its own with a stand-in for sendmail: mail() in the PHP CLI and in PHP-FPM
     * runs the program with the arguments of the variable.
     */
    public function testPhpSendmailPathFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('sendmail', ['PHP_SENDMAIL_PATH' => '/opt/image-tests/capture-mail.sh --from image-test@example.test']);
        $this->assertCommandSucceeds('mkdir -p -m 1777 /tmp/image-tests/captured-mail', $container);
        self::assertSame('sent', $this->stdout("php -r 'echo mail(\"someone@example.test\", \"Image test from \" . PHP_SAPI, \"Sent by the image tests\") ? \"sent\" : \"not sent\";'", $container));
        self::assertSame('sent', $this->phpFpmBody($container, 'test=mail'));
        foreach (['cli', 'fpm-fcgi'] as $sapi) {
            $mail = str_replace("\r\n", "\n", $this->stdout('cat $(grep -l "^Subject: Image test from ' . $sapi . '" /tmp/image-tests/captured-mail/*.eml) < /dev/null', $container));
            self::assertStringContainsString("Arguments: --from image-test@example.test\n", $mail, $sapi);
            self::assertStringContainsString("To: someone@example.test\n", $mail, $sapi);
        }
    }

    public function testPhpDismodDisablesExtensionsOfTheCliAndPhpFpm(): void
    {
        $container = $this->environmentContainer();
        foreach (['php -m', 'php-fpm -m'] as $command) {
            $modules = $this->lines($this->stdout($command, $container));
            foreach (['redis', 'pgsql', 'pdo_pgsql'] as $module) {
                self::assertNotContains($module, $modules, sprintf('PHP_DISMOD=%s should disable %s in %s', static::ENVIRONMENT['PHP_DISMOD'], $module, $command));
            }

            self::assertContains('apcu', $modules, 'the other extensions stay');
        }
    }

    public function testComposerVersionFromTheEnvironment(): void
    {
        self::assertMatchesRegularExpression('/^Composer version 1\.\d+\.\d+ /m', $this->stdout('composer --version 2>/dev/null', $this->environmentContainer()));
    }
}
