<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\ListeningSockets;
use Webdevops\Build\ImageTests\ImageTestCase;

use function implode;

/**
 * The SSH server of the development images runs, listens on port 22 and lets root and the application user log in with
 * the password dev. Its log reaches the file of LOG_STDERR.
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

    /**
     * sshd logs to its stderr, which supervisord writes to the file of LOG_STDERR in the environment container, where
     * sshd listens on port 2222 (SERVICE_SSH_OPTS). Through syslog, its messages were lost: syslog-ng drops the facility
     * auth.
     */
    public function testSshLogsToTheFileFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($container): void {
            self::assertTrue(ListeningSockets::read($container)->isListening(2222), 'sshd should listen on port 2222');
        });
        $login = $this->execute('ssh -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p 2222 image-test-nobody@127.0.0.1 true', $container);
        self::assertNotSame(0, $login->exitCode, $login->describe());
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('/Invalid user image-test-nobody from 127\.0\.0\.1 /', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
    }
}
