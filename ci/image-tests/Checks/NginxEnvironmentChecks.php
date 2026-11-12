<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The Nginx variables, and LOG_STDOUT and LOG_STDERR for Nginx, see docs/environment-variables.md#web-server and
 * #container.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait NginxEnvironmentChecks
{
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

    /**
     * SERVICE_NGINX_OPTS="-c /opt/image-tests/nginx.conf", in a container of its own: Nginx runs with the configuration
     * of Fixtures/, which answers on port 8080, and not with the one of the image.
     */
    public function testNginxOptionsFromTheEnvironment(): void
    {
        $container = $this->startContainer('nginx-options', ['SERVICE_NGINX_OPTS' => '-c /opt/image-tests/nginx.conf']);
        self::assertSame("nginx.conf of the image tests\n", $this->request('http://localhost:8080/', container: $container));
        self::assertNotSame(0, $this->execute('curl --silent http://localhost/', $container)->exitCode, 'Nothing should listen on port 80.');
    }

    /**
     * Nginx writes its access log to LOG_STDOUT and its errors to LOG_STDERR: a file it may not read gets 403 and an
     * error.
     */
    public function testNginxLogsToTheFilesFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->request('http://localhost/', container: $container);
        $file = static::ENVIRONMENT['WEB_DOCUMENT_ROOT'] . '/image-test-forbidden.html';
        $this->assertCommandSucceeds('touch ' . $file . ' && chmod 000 ' . $file, $container);
        self::assertSame('403', $this->stdout("curl --silent --output /dev/null --write-out '%{http_code}' http://localhost/image-test-forbidden.html", $container));
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('#"GET /image-test-forbidden\.html HTTP/1\.1" 403 #', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
            self::assertMatchesRegularExpression('#\[error\] .*image-test-forbidden\.html" failed \(13: Permission denied\)#', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
    }
}
