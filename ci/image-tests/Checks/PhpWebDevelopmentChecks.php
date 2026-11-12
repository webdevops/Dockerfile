<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function in_array;
use function strtolower;

/**
 * Requests to PHP-FPM in php-nginx-dev and php-apache-dev: Xdebug, but not Blackfire; and WEB_NO_CACHE_PATTERN.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpWebDevelopmentChecks
{
    use PhpWebChecks;

    public function testPhpFpmLoadsXdebug(): void
    {
        $modules = $this->phpFpmModules();
        self::assertContains('xdebug', $modules);
        self::assertNotContains('blackfire', $modules);
    }

    /**
     * A file that matches WEB_NO_CACHE_PATTERN in the environment container: Nginx sends it with
     * "Cache-Control: private" and without Expires, Apache without ETag and with "Cache-Control: no-cache".
     */
    public function testNoCachePatternFromTheEnvironment(): void
    {
        $headers = strtolower($this->request('http://localhost/no-cache.imagetest', '--head', container: $this->environmentContainer()));

        if (in_array('nginx', self::services(), true)) {
            self::assertStringContainsString("\ncache-control: private\r\n", $headers);
            self::assertStringNotContainsString("\nexpires:", $headers);
        } else {
            self::assertStringNotContainsString("\netag:", $headers);
            self::assertStringContainsString('no-cache', $headers);
        }
    }
}
