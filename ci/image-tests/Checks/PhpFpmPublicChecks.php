<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * PHP-FPM runs and listens on port 9000 of every address, for a web server in another container.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmPublicChecks
{
    use PhpFpmServiceChecks;

    public function testPhpFpmListensOnAllAddresses(): void
    {
        $this->assertListening(9000, '::', '0.0.0.0');
    }
}
