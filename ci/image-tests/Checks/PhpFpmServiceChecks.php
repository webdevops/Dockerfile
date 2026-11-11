<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * PHP-FPM runs.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmServiceChecks
{
    public function testPhpFpmRuns(): void
    {
        $this->assertServiceRunning('php-fpm');
    }
}
