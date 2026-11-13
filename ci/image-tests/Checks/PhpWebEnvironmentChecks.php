<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * WEB_PHP_TIMEOUT in the environment container, see docs/environment-variables.md#long-requests. The PHP variables
 * are checked by PhpEnvironmentChecks, over FastCGI.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpWebEnvironmentChecks
{
    /**
     * A PHP request that takes longer than WEB_PHP_TIMEOUT (2 s) gets 504 from the web server.
     */
    public function testWebServerAnswers504AfterWebPhpTimeout(): void
    {
        $container = $this->environmentContainer();
        // wait for the web server and PHP-FPM
        $this->request('http://localhost/php-test.php?test=sha1', container: $container);

        self::assertSame('504', $this->statusCode('http://localhost/php-test.php?test=sleep&seconds=5', container: $container));
    }
}
