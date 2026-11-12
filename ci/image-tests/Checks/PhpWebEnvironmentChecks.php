<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The PHP variables as PHP-FPM sees them through the web server, and WEB_PHP_TIMEOUT, in the environment container,
 * see docs/environment-variables.md.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpWebEnvironmentChecks
{
    public function testPhpFpmSettingsThroughTheWebServer(): void
    {
        $names = ['memory_limit', 'max_execution_time', 'max_input_time', 'max_input_vars', 'date.timezone', 'sendmail_path'];
        $body = $this->request(
            'http://localhost/php-test.php?test=ini_get&names=' . implode(',', $names),
            container: $this->environmentContainer(),
        );

        self::assertSame([
            'memory_limit' => static::ENVIRONMENT['PHP_MEMORY_LIMIT'],
            'max_execution_time' => static::ENVIRONMENT['PHP_MAX_EXECUTION_TIME'],
            // from SERVICE_PHPFPM_OPTS=-dmax_input_time=120
            'max_input_time' => '120',
            'max_input_vars' => static::ENVIRONMENT['php.max_input_vars'],
            'date.timezone' => static::ENVIRONMENT['PHP_DATE_TIMEZONE'],
            'sendmail_path' => static::ENVIRONMENT['PHP_SENDMAIL_PATH'],
        ], json_decode($body, true), $body);
    }

    /**
     * A PHP request that takes longer than WEB_PHP_TIMEOUT (2 s) gets 504 from the web server.
     */
    public function testWebServerAnswers504AfterWebPhpTimeout(): void
    {
        $container = $this->environmentContainer();
        // wait for the web server and PHP-FPM
        $this->request('http://localhost/php-test.php?test=sha1', container: $container);

        self::assertSame('504', $this->stdout(
            "curl --silent --output /dev/null --write-out '%{http_code}' 'http://localhost/php-test.php?test=sleep&seconds=5'",
            $container,
        ));
    }
}
