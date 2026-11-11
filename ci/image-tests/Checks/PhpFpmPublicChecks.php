<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\ListeningSockets;
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
        $this->eventually(function (): void {
            $sockets = ListeningSockets::read(self::container());
            self::assertTrue(
                $sockets->isListening(9000, '::') || $sockets->isListening(9000, '0.0.0.0'),
                'PHP-FPM should listen on [::]:9000 or 0.0.0.0:9000; listening: ' . $sockets->describe(),
            );
        }, 10);
    }
}
