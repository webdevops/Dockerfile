<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The Apache variables in the environment container, see docs/environment-variables.md#web-server.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ApacheEnvironmentChecks
{
    public function testApacheAliasDomainFromTheEnvironment(): void
    {
        self::assertMatchesRegularExpression(
            '/^\s*ServerAlias image-test\.example$/m',
            $this->stdout('cat /opt/docker/etc/httpd/vhost.conf', $this->environmentContainer()),
        );
    }
}
