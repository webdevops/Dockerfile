<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function implode;

/**
 * The SSH server of the development images runs, listens on port 22 and lets root and the application user log in with
 * the password dev.
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

    public function testSshLoginWithThePasswordDev(): void
    {
        $this->assertListening(22);
        $result = $this->execute(implode(' && ', [
            "printf '#!/bin/sh\\necho dev\\n' > /tmp/image-test-askpass",
            'chmod +x /tmp/image-test-askpass',
            'SSH_ASKPASS=/tmp/image-test-askpass SSH_ASKPASS_REQUIRE=force ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null '
                . '-o PreferredAuthentications=password,keyboard-interactive application@127.0.0.1 id -un',
        ]));
        self::assertSame([0, "application\n"], [$result->exitCode, $result->stdout], $result->describe());
    }
}
