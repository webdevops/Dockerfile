<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * PHP-FPM runs and listens on port 9000 of localhost only, for the web server in the same container.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmLocalOnlyChecks
{
    use PhpFpmServiceChecks;

    public function testPhpFpmListensOnLocalhostOnly(): void
    {
        $this->assertListening(9000, '::1', '127.0.0.1');
        $this->assertNotListening(9000, '0.0.0.0', '::');
    }
}
