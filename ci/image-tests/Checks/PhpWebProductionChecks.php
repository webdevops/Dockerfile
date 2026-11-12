<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Requests to PHP-FPM in php-nginx and php-apache: no debugger.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpWebProductionChecks
{
    use PhpWebChecks;

    public function testPhpFpmLoadsNoDebugger(): void
    {
        $modules = $this->phpFpmModules();
        self::assertNotContains('xdebug', $modules);
        self::assertNotContains('blackfire', $modules);
    }
}
