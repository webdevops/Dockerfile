<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * LOG_STDOUT and LOG_STDERR for Apache, see docs/environment-variables.md#container. SERVICE_APACHE_OPTS is checked by
 * ServiceChecks.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ApacheEnvironmentChecks
{
    /**
     * Apache writes its access log to LOG_STDOUT and its start messages, with its command line, to LOG_STDERR.
     */
    public function testApacheLogsToTheFilesFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->request('http://localhost/', container: $container);
        $this->execute('curl --silent --output /dev/null http://localhost/image-test-log-marker', $container);
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('#"GET /image-test-log-marker HTTP/1\.1" \d{3} #', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
            self::assertMatchesRegularExpression('#AH00094: Command line: .*-D ?IMAGE_TEST#', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
    }
}
