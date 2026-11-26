<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The log variables of PHP-FPM and syslog-ng, see docs/environment-variables.md#logs-of-the-services. Only in
 * webdevops/php: the images built on it have the same configuration of PHP-FPM and syslog-ng, see AGENTS.md. The
 * variables of Nginx and Apache are checked by NginxEnvironmentChecks and ApacheEnvironmentChecks.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ServiceLogChecks
{
    /** @var array<string, string> */
    private const array SERVICE_LOG_FILES = [
        'SERVICE_PHPFPM_ACCESS_LOG' => '/tmp/image-test-phpfpm-access.log',
        'SERVICE_PHPFPM_ERROR_LOG' => '/tmp/image-test-phpfpm-error.log',
        'SERVICE_SYSLOG_LOG' => '/tmp/image-test-syslog.log',
        'SERVICE_SYSLOG_ERROR_LOG' => '/tmp/image-test-syslog-error.log',
    ];

    /**
     * Each variable sends its log to its file, created as root:root 0644, and the log no longer reaches the container
     * log: the access log of PHP-FPM, the errors of a PHP script and the start message of PHP-FPM, and the messages of
     * syslog by their level.
     */
    public function testServiceLogsGoToTheFilesOfTheirVariables(): void
    {
        $files = self::SERVICE_LOG_FILES;
        $container = $this->startTestContainer('service-log-files', $files);
        self::assertSame('logged', $this->phpFpmBody($container, 'test=error_log'));
        $this->eventually(function () use ($container, $files): void {
            $this->assertCommandSucceeds('logger -t image-test -p user.info service-log-info-marker', $container);
            $this->assertCommandSucceeds('logger -t image-test -p user.err service-log-err-marker', $container);
            self::assertMatchesRegularExpression('#"GET /php-test\.php\?test=error_log" 200 #', $this->stdout('cat ' . $files['SERVICE_PHPFPM_ACCESS_LOG'], $container));
            $errors = $this->stdout('cat ' . $files['SERVICE_PHPFPM_ERROR_LOG'], $container);
            self::assertStringContainsString('image-test-from-php-fpm', $errors);
            self::assertStringContainsString('ready to handle connections', $errors);
            self::assertStringContainsString('image-test: service-log-info-marker', $this->stdout('cat ' . $files['SERVICE_SYSLOG_LOG'], $container));
            self::assertStringContainsString('image-test: service-log-err-marker', $this->stdout('cat ' . $files['SERVICE_SYSLOG_ERROR_LOG'], $container));
        });
        foreach ($files as $variable => $file) {
            self::assertSame("644 root:root\n", $this->stdout("stat -c '%a %U:%G' " . $file, $container), $variable);
        }

        $log = $container->logs();
        foreach (['GET /php-test.php?test=error_log', 'image-test-from-php-fpm', 'ready to handle connections', 'service-log-info-marker', 'service-log-err-marker'] as $line) {
            self::assertStringNotContainsString($line, $log);
        }
    }

    /**
     * /dev/null turns a log off: the access log of PHP-FPM and the messages of syslog up to the level warning reach
     * neither the container log nor a file, and /dev/null keeps its owner and mode. The other logs stay in the container
     * log.
     */
    public function testServiceLogsGoNowhereWithDevNull(): void
    {
        $container = $this->startTestContainer('service-log-dev-null', [
            'SERVICE_PHPFPM_ACCESS_LOG' => '/dev/null',
            'SERVICE_SYSLOG_LOG' => '/dev/null',
        ]);
        self::assertSame('logged', $this->phpFpmBody($container, 'test=error_log'));
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('logger -t image-test -p user.err dev-null-err-marker', $container);
            [, $stderr] = $container->logStreams();
            self::assertStringContainsString('image-test-from-php-fpm', $stderr);
            self::assertStringContainsString('image-test: dev-null-err-marker', $stderr);
        });
        $this->assertCommandSucceeds('logger -t image-test -p user.info dev-null-info-marker', $container);
        // the error after the message shows that syslog-ng has handled the message before it
        $this->assertCommandSucceeds('logger -t image-test -p user.err dev-null-after-marker', $container);
        $this->eventually(function () use ($container): void {
            self::assertStringContainsString('dev-null-after-marker', $container->logs());
        });
        $log = $container->logs();
        self::assertStringNotContainsString('GET /php-test.php?test=error_log', $log);
        self::assertStringNotContainsString('dev-null-info-marker', $log);
        self::assertSame("character special file 666 root:root\n", $this->stdout("stat -c '%F %a %U:%G' /dev/null", $container));
    }
}
