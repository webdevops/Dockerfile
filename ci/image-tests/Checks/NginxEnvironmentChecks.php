<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The Nginx variables in the environment container, see docs/environment-variables.md#web-server.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait NginxEnvironmentChecks
{
    public function testNginxAliasDomainFromTheEnvironment(): void
    {
        self::assertMatchesRegularExpression(
            '/^\s*server_name\s+_ image-test\.example\b/m',
            $this->stdout('nginx -T 2>/dev/null', $this->environmentContainer()),
        );
    }

    /**
     * A request body larger than SERVICE_NGINX_CLIENT_MAX_BODY_SIZE (1k) gets 413.
     */
    public function testNginxClientMaxBodySizeFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        // wait for Nginx
        $this->request('http://localhost/', container: $container);

        self::assertSame('413', $this->stdout(
            "head -c 2048 /dev/zero | curl --silent --output /dev/null --write-out '%{http_code}' --data-binary @- http://localhost/",
            $container,
        ));
    }
}
