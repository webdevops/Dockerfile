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

    /**
     * SERVICE_APACHE_ACCESS_LOG and SERVICE_APACHE_ERROR_LOG, in a container of its own: Apache writes its access log
     * and its errors to these files, created as root:root 0644, and no longer to the container log. A file it may not
     * read gets 403 and an error. SERVICE_APACHE_ACCESS_LOG=/dev/null turns the access log off.
     */
    public function testApacheLogsToTheFilesOfItsLogVariables(): void
    {
        $access = '/tmp/image-test-apache-access.log';
        $error = '/tmp/image-test-apache-error.log';
        $container = $this->startTestContainer('apache-log-files', ['SERVICE_APACHE_ACCESS_LOG' => $access, 'SERVICE_APACHE_ERROR_LOG' => $error]);
        $this->request('http://localhost/', container: $container);
        $this->assertCommandSucceeds('touch /app/image-test-forbidden.html && chmod 000 /app/image-test-forbidden.html', $container);
        self::assertSame('403', $this->statusCode('http://localhost/image-test-forbidden.html', container: $container));
        $this->eventually(function () use ($container, $access, $error): void {
            self::assertMatchesRegularExpression('#"GET /image-test-forbidden\.html HTTP/1\.1" 403 #', $this->stdout('cat ' . $access, $container));
            self::assertMatchesRegularExpression('#AH00132: .*image-test-forbidden\.html#', $this->stdout('cat ' . $error, $container));
        });
        self::assertSame("644 root:root\n644 root:root\n", $this->stdout("stat -c '%a %U:%G' " . $access . ' ' . $error, $container));
        self::assertStringNotContainsString('image-test-forbidden.html', $container->logs());

        $silent = $this->startTestContainer('apache-log-dev-null', ['SERVICE_APACHE_ACCESS_LOG' => '/dev/null']);
        $this->request('http://localhost/', container: $silent);
        $this->assertCommandSucceeds('touch /app/image-test-forbidden.html && chmod 000 /app/image-test-forbidden.html', $silent);
        self::assertSame('403', $this->statusCode('http://localhost/image-test-forbidden.html', container: $silent));
        $this->eventually(function () use ($silent): void {
            // the error still reaches the container log
            self::assertMatchesRegularExpression('#AH00132: .*image-test-forbidden\.html#', $silent->logStreams()[1]);
        });
        self::assertStringNotContainsString('"GET /image-test-forbidden.html', $silent->logs());
    }
}
