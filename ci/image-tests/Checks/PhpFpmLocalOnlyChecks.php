<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\ListeningSockets;
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
        $this->eventually(function (): void {
            $sockets = ListeningSockets::read(self::container());
            self::assertFalse($sockets->isListening(9000, '0.0.0.0'), 'PHP-FPM should not listen on 0.0.0.0:9000');
            self::assertFalse($sockets->isListening(9000, '::'), 'PHP-FPM should not listen on [::]:9000');
            self::assertTrue(
                $sockets->isListening(9000, '::1') || $sockets->isListening(9000, '127.0.0.1'),
                'PHP-FPM should listen on [::1]:9000 or 127.0.0.1:9000; listening: ' . $sockets->describe(),
            );
        }, 10);
    }
}
