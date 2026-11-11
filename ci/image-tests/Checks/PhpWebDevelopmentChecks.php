<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Requests to PHP-FPM in php-nginx-dev and php-apache-dev: Xdebug, but not Blackfire.
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
}
