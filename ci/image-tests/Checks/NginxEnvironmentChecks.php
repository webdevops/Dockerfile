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
        self::assertSame('413', $this->statusCode('http://localhost/', '--data-binary @-', $container, 'head -c 2048 /dev/zero'));
    }

    /**
     * SERVICE_NGINX_OPTS="-c /opt/image-tests/nginx.conf", in a container of its own: Nginx runs with the configuration
     * of Fixtures/, which answers on port 8080, and not with the one of the image.
     */
    public function testNginxOptionsFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('nginx-options', ['SERVICE_NGINX_OPTS' => '-c /opt/image-tests/nginx.conf']);
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
        self::assertSame('403', $this->statusCode('http://localhost/image-test-forbidden.html', container: $container));
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('#"GET /image-test-forbidden\.html HTTP/1\.1" 403 #', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
            self::assertMatchesRegularExpression('#\[error\] .*image-test-forbidden\.html" failed \(13: Permission denied\)#', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
    }

    /**
     * Nginx writes the messages that belong to no server, those of its main error log, to LOG_STDERR, not to LOG_STDOUT:
     * the master process logs that a worker process ended on SIGKILL.
     */
    public function testNginxMainErrorLogToTheFileFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->request('http://localhost/', container: $container);
        $this->assertCommandSucceeds(
            'for process in /proc/[0-9]*; do if tr "\\000" " " < $process/cmdline 2>/dev/null | grep -q "^nginx: worker process"; then kill -9 ${process#/proc/}; break; fi; done',
            $container,
        );
        $exited = '#\[alert\] .*worker process \d+ exited on signal 9#';
        $this->eventually(function () use ($container, $exited): void {
            self::assertMatchesRegularExpression($exited, $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
        self::assertDoesNotMatchRegularExpression($exited, $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
    }

    /**
     * SERVICE_NGINX_ACCESS_LOG and SERVICE_NGINX_ERROR_LOG, in a container of its own: Nginx writes its access log and
     * its errors, also those of its main error log, to these files, created as root:root 0644, and no longer to the
     * container log. A file it may not read gets 403 and an error; a worker process ended on SIGKILL an alert.
     */
    public function testNginxLogsToTheFilesOfItsLogVariables(): void
    {
        $access = '/tmp/image-test-nginx-access.log';
        $error = '/tmp/image-test-nginx-error.log';
        $container = $this->startTestContainer('nginx-log-files', ['SERVICE_NGINX_ACCESS_LOG' => $access, 'SERVICE_NGINX_ERROR_LOG' => $error]);
        // wait for the web server: / of the empty /app may answer 403
        $this->eventually(function () use ($container): void {
            self::assertNotSame('000', $this->statusCode('http://localhost/', container: $container));
        });
        $this->assertCommandSucceeds('touch /app/image-test-forbidden.html && chmod 000 /app/image-test-forbidden.html', $container);
        self::assertSame('403', $this->statusCode('http://localhost/image-test-forbidden.html', container: $container));
        $this->assertCommandSucceeds(
            'for process in /proc/[0-9]*; do if tr "\\000" " " < $process/cmdline 2>/dev/null | grep -q "^nginx: worker process"; then kill -9 ${process#/proc/}; break; fi; done',
            $container,
        );
        $this->eventually(function () use ($container, $access, $error): void {
            self::assertMatchesRegularExpression('#"GET /image-test-forbidden\.html HTTP/1\.1" 403 #', $this->stdout('cat ' . $access, $container));
            $errors = $this->stdout('cat ' . $error, $container);
            self::assertMatchesRegularExpression('#\[error\] .*image-test-forbidden\.html" failed \(13: Permission denied\)#', $errors);
            self::assertMatchesRegularExpression('#\[alert\] .*worker process \d+ exited on signal 9#', $errors);
        });
        self::assertSame("644 root:root\n644 root:root\n", $this->stdout("stat -c '%a %U:%G' " . $access . ' ' . $error, $container));
        $log = $container->logs();
        self::assertStringNotContainsString('image-test-forbidden.html', $log);
        self::assertStringNotContainsString('exited on signal 9', $log);
    }

    /**
     * SERVICE_NGINX_ACCESS_LOG=/dev/null turns the access log of Nginx off; its errors stay in the container log.
     */
    public function testNginxAccessLogGoesNowhereWithDevNull(): void
    {
        $container = $this->startTestContainer('nginx-log-dev-null', ['SERVICE_NGINX_ACCESS_LOG' => '/dev/null']);
        // wait for the web server: / of the empty /app may answer 403
        $this->eventually(function () use ($container): void {
            self::assertNotSame('000', $this->statusCode('http://localhost/', container: $container));
        });
        $this->assertCommandSucceeds('touch /app/image-test-forbidden.html && chmod 000 /app/image-test-forbidden.html', $container);
        self::assertSame('403', $this->statusCode('http://localhost/image-test-forbidden.html', container: $container));
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('#\[error\] .*image-test-forbidden\.html" failed#', $container->logStreams()[1]);
        });
        self::assertStringNotContainsString('"GET /image-test-forbidden.html', $container->logs());
    }
}
