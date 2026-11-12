<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_map;
use function json_decode;
use function max;
use function microtime;
use function preg_match;
use function range;
use function sleep;
use function sprintf;
use function usleep;

/**
 * The PHP-FPM variables, see docs/environment-variables.md#php-fpm. The tests request PHP-FPM over FastCGI without a
 * web server and watch its worker processes. The settings of the process manager get containers of their own, so that
 * the other tests do not change the number of processes.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmEnvironmentChecks
{
    /**
     * FPM_RLIMIT_FILES=4321 and FPM_RLIMIT_CORE=1024: the limits of the worker that runs the request.
     */
    public function testPhpFpmResourceLimitsFromTheEnvironment(): void
    {
        $process = json_decode($this->phpFpmBody($this->environmentContainer(), 'test=process'), true);
        self::assertIsArray($process);
        self::assertMatchesRegularExpression('/^Max open files\s+4321\s+4321\s/m', $process['limits']);
        self::assertMatchesRegularExpression('/^Max core file size\s+1024\s+1024\s/m', $process['limits']);
    }

    /**
     * FPM_REQUEST_TERMINATE_TIMEOUT=4: PHP-FPM ends a request that sleeps for 8 s after 4 s, without a response.
     * PHP_MAX_EXECUTION_TIME does not stop it, because time in sleep() does not count.
     */
    public function testPhpFpmRequestTerminateTimeoutFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $start = microtime(true);
        $result = $this->phpFpmRequest($container, 'test=sleep&seconds=8');
        $seconds = microtime(true) - $start;
        self::assertSame(2, $result->exitCode, 'PHP-FPM should close the connection before the request ends: ' . $result->describe());
        self::assertStringNotContainsString('slept', $result->stdout);
        self::assertGreaterThan(3.5, $seconds);
        self::assertLessThan(7, $seconds);
    }

    /**
     * FPM_PROCESS_MAX=2: with four slow requests at once, PHP-FPM runs no more than two workers, although the pool would
     * allow five.
     */
    public function testPhpFpmProcessMaxFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $workers = $this->phpFpmWorkersDuring($container, 4, 4, 2.5);
        self::assertSame(2, $workers, 'workers while four requests run');
    }

    /**
     * fpm.global.log_level=debug: PHP-FPM writes debug messages to the container log.
     */
    public function testPhpFpmGlobalDirectiveFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $this->eventually(static function () use ($container): void {
            self::assertMatchesRegularExpression('/DEBUG: pid \d+, fpm_/', $container->logs());
        });
    }

    /**
     * fpm.pool.ping.path=/image-test-ping: PHP-FPM answers the path itself.
     */
    public function testPhpFpmPoolDirectiveFromTheEnvironment(): void
    {
        self::assertSame('pong', $this->phpFpmBody($this->environmentContainer(), '', script: static::ENVIRONMENT['fpm.pool.ping.path']));
    }

    /**
     * FPM_PM_START_SERVERS=3, FPM_PM_MIN_SPARE_SERVERS=3, FPM_PM_MAX_SPARE_SERVERS=4 and FPM_PM_MAX_CHILDREN=8, in a
     * container of their own: three workers at the start, three idle ones besides three busy ones, eight at most
     * under load, and four once the load is gone. The defaults would give 2, 4, 5 and 3.
     */
    public function testPhpFpmProcessManagerFromTheEnvironment(): void
    {
        $container = $this->startContainer('fpm-process-manager', [
            'FPM_PM_START_SERVERS' => '3',
            'FPM_PM_MIN_SPARE_SERVERS' => '3',
            'FPM_PM_MAX_SPARE_SERVERS' => '4',
            'FPM_PM_MAX_CHILDREN' => '8',
            'fpm.pool.pm.status_path' => '/image-test-status',
        ]);
        $this->eventually(function () use ($container): void {
            self::assertSame(3, $this->phpFpmWorkers($container), 'workers at the start');
        });
        self::assertSame(6, $this->phpFpmWorkersDuring($container, 3, 6, 3), 'workers while three requests run: three busy, three idle');
        $this->phpFpmWorkersDuring($container, 12, 5, 4);
        $status = $this->phpFpmBody($container, '', script: '/image-test-status');
        self::assertMatchesRegularExpression('/^max active processes:\s+8$/m', $status, 'FPM_PM_MAX_CHILDREN');
        self::assertMatchesRegularExpression('/^max children reached:\s+[1-9]/m', $status, 'FPM_PM_MAX_CHILDREN');
        $this->waitFor(function () use ($container): void {
            self::assertSame(4, $this->phpFpmWorkers($container), 'idle workers once the requests ended');
        }, 10, 1);
    }

    /**
     * FPM_MAX_REQUESTS=2 and FPM_PROCESS_IDLE_TIMEOUT=2s, in a container of their own with fpm.pool.pm=ondemand and one
     * worker: a worker serves two requests, then a new one starts; an idle worker exits after 2 s.
     */
    public function testPhpFpmMaxRequestsAndIdleTimeoutFromTheEnvironment(): void
    {
        $container = $this->startContainer('fpm-ondemand', [
            'fpm.pool.pm' => 'ondemand',
            'FPM_PM_MAX_CHILDREN' => '1',
            'FPM_MAX_REQUESTS' => '2',
            'FPM_PROCESS_IDLE_TIMEOUT' => '2s',
        ]);
        $processIds = array_map(function () use ($container): int {
            $process = json_decode($this->phpFpmBody($container, 'test=process'), true);
            self::assertIsArray($process);
            return $process['pid'];
        }, range(1, 3));
        self::assertSame($processIds[0], $processIds[1], 'the first two requests in one worker');
        self::assertNotSame($processIds[1], $processIds[2], 'a new worker after two requests');
        self::assertSame(1, $this->phpFpmWorkers($container));
        sleep(4);
        self::assertSame(0, $this->phpFpmWorkers($container), 'the idle worker exits after 2 s');
    }

    /**
     * Sends $requests requests that sleep for $seconds to PHP-FPM at once, and returns the largest number of workers
     * seen until $within seconds after they started. Waits until the requests ended.
     */
    private function phpFpmWorkersDuring(Container $container, int $requests, int $seconds, float $within): int
    {
        $this->assertCommandSucceeds(sprintf(
            'for request in $(seq %d); do (php -n /opt/image-tests/fastcgi-request.php 127.0.0.1:9000 /app/php-test.php "test=sleep&seconds=%d" < /dev/null > /dev/null 2>&1 &); done',
            $requests,
            $seconds,
        ), $container);
        $start = microtime(true);
        $workers = 0;
        while (microtime(true) - $start < $within) {
            $workers = max($workers, $this->phpFpmWorkers($container));
            usleep(250_000);
        }

        $this->waitFor(function () use ($container): void {
            self::assertSame(0, preg_match('/fastcgi-request/', implode("\n", $this->commandLines($container))), 'the requests should have ended');
        }, 20, 1);

        return $workers;
    }
}
