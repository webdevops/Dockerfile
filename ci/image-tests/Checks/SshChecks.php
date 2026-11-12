<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The SSH server of webdevops/php-dev runs and listens on port 22.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait SshChecks
{
    public function testSshListens(): void
    {
        $this->assertListening(22);
    }

    public function testSshRuns(): void
    {
        $this->assertServiceRunning('ssh');
    }
}
