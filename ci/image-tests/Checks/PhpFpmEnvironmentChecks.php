<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_map;
use function ctype_digit;
use function escapeshellarg;
use function explode;
use function implode;
use function intdiv;
use function json_decode;
use function max;
use function microtime;
use function min;
use function preg_match;
use function preg_quote;
use function range;
use function sleep;
use function sprintf;
use function trim;
use function usleep;
use function vsprintf;

/**
 * The PHP-FPM variables, see docs/environment-variables.md#php-fpm, and LOG_STDOUT and LOG_STDERR for PHP-FPM, see
 * docs/environment-variables.md#container. The tests request PHP-FPM over FastCGI without a web server and watch its
 * worker processes. The settings of the process manager get containers of their own, so that the other tests do not
 * change the number of processes.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmEnvironmentChecks
{
    /**
     * The message PHP-FPM logs when it has started.
     */
    private const string PHP_FPM_READY = 'ready to handle connections';

    /**
     * The line of the access log of PHP-FPM for a request of test=error_log, see phpFpmCommand().
     */
    private const string PHP_FPM_ACCESS_LOG_LINE = '#"GET /php-test\.php\?test=error_log" 200 /app/php-test\.php #';

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
     * The request lifts PHP_MAX_EXECUTION_TIME, which counts the time in sleep() in thread-safe PHP.
     */
    public function testPhpFpmRequestTerminateTimeoutFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $start = microtime(true);
        $result = $this->phpFpmRequest($container, 'test=sleep&seconds=8&time_limit=0');
        $seconds = microtime(true) - $start;
        self::assertSame(2, $result->exitCode, 'PHP-FPM should close the connection before the request ends: ' . $result->describe());
        self::assertStringNotContainsString('slept', $result->stdout);
        self::assertGreaterThan(3.5, $seconds);
        self::assertLessThan(7, $seconds);
    }

    /**
     * FPM_PROCESS_MAX=2: with four slow requests at once, PHP-FPM runs no more than two workers, although the pool would
     * allow more.
     */
    public function testPhpFpmProcessMaxFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $workers = $this->phpFpmWorkersDuring($container, 4, 4, 2.5);
        self::assertSame(2, $workers, 'workers while four requests run');
    }

    /**
     * fpm.global.log_level=debug: PHP-FPM writes debug messages to its error log, the file of LOG_STDERR.
     */
    public function testPhpFpmGlobalDirectiveFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->phpFpmBody($container, 'test=sha1');
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('/DEBUG: pid \d+, fpm_/', $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container));
        });
    }

    /**
     * LOG_STDOUT and LOG_STDERR for PHP-FPM: its access log goes to the file of LOG_STDOUT, its own messages and the
     * errors of PHP scripts to the one of LOG_STDERR, and none of them to the container log. A worker may not open the
     * file of LOG_STDERR, which belongs to root; the master process of PHP-FPM writes what the worker logs instead.
     */
    public function testPhpFpmLogsToTheFilesFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        self::assertSame('logged', $this->phpFpmBody($container, 'test=error_log'));
        $this->eventually(function () use ($container): void {
            // the development images log the requests in a format of their own
            self::assertMatchesRegularExpression(self::PHP_FPM_ACCESS_LOG_LINE, $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDOUT'], $container));
            $errors = $this->stdout('cat ' . static::ENVIRONMENT['LOG_STDERR'], $container);
            self::assertStringContainsString('PHP message: image-test-from-php-fpm', $errors);
            self::assertStringContainsString(self::PHP_FPM_READY, $errors);
        });
        $log = $container->logs();
        self::assertDoesNotMatchRegularExpression(self::PHP_FPM_ACCESS_LOG_LINE, $log);
        self::assertStringNotContainsString('image-test-from-php-fpm', $log);
        self::assertStringNotContainsString(self::PHP_FPM_READY, $log);
    }

    /**
     * Without LOG_STDOUT and LOG_STDERR, in the container of the class, /docker.stdout and /docker.stderr are the stdout
     * and stderr of the container: PHP-FPM writes its access log to the stdout, its own messages and the errors of PHP
     * scripts to the stderr.
     */
    public function testPhpFpmLogsToTheContainerLogByDefault(): void
    {
        $container = self::container();
        self::assertSame('logged', $this->phpFpmBody($container, 'test=error_log'));
        $this->eventually(function () use ($container): void {
            [$stdout, $stderr] = $container->logStreams();
            self::assertMatchesRegularExpression(self::PHP_FPM_ACCESS_LOG_LINE, $stdout);
            self::assertStringContainsString('PHP message: image-test-from-php-fpm', $stderr);
            self::assertStringContainsString(self::PHP_FPM_READY, $stderr);
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
     * fpm.pool.clear_env=yes and fpm.pool.access.log, in a container of their own: two directives that docker.conf of
     * the official PHP image sets too, and PHP-FPM reads docker.conf after application.conf. With clear_env, a worker
     * no longer gets the variables of the container, e.g. APPLICATION_PATH, which it gets in the container of the
     * class; the access log goes to the file instead of the container log. application.conf stays as the image has it.
     */
    public function testPhpFpmPoolDirectivesOfDockerConfFromTheEnvironment(): void
    {
        self::assertSame('/app', $this->phpFpmBody(self::container(), 'test=application_path'), 'without fpm.pool.clear_env');
        $container = $this->startTestContainer('fpm-docker-conf', [
            'fpm.pool.clear_env' => 'yes',
            'fpm.pool.access.log' => '/app/image-test-fpm-access.log',
        ]);
        self::assertSame('not set', $this->phpFpmBody($container, 'test=application_path'), 'fpm.pool.clear_env=yes');
        $this->waitFor(function () use ($container): void {
            $log = $this->execute('cat /app/image-test-fpm-access.log', $container);
            self::assertStringContainsString('php-test.php?test=application_path', $log->stdout, 'fpm.pool.access.log: ' . $log->describe());
        });
        self::assertStringNotContainsString('image-test-fpm-access.log', $this->stdout('cat /opt/docker/etc/php/fpm/pool.d/application.conf', $container));
    }

    /**
     * FPM_PM_START_SERVERS=3, FPM_PM_MIN_SPARE_SERVERS=3, FPM_PM_MAX_SPARE_SERVERS=4 and FPM_PM_MAX_CHILDREN=8, in a
     * container of their own: three workers at the start, three idle ones besides three busy ones, eight at most
     * under load, and four once the load is gone. The values of the official image, with FPM_AUTO_SIZE=0, would give 2,
     * 4, 5 and 3.
     */
    public function testPhpFpmProcessManagerFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('fpm-process-manager', [
            'FPM_PM_START_SERVERS' => '3',
            'FPM_PM_MIN_SPARE_SERVERS' => '3',
            'FPM_PM_MAX_SPARE_SERVERS' => '4',
            'FPM_PM_MAX_CHILDREN' => '8',
            'fpm.pool.pm.status_path' => '/image-test-status',
        ]);
        $this->eventually(function () use ($container): void {
            self::assertSame(3, $this->phpFpmWorkers($container), 'workers at the start');
        });
        self::assertSame(6, $this->phpFpmWorkersDuring($container, 3, 6, 4.5), 'workers while three requests run: three busy, three idle');
        $this->phpFpmWorkersDuring($container, 12, 5, 4);
        $status = $this->phpFpmBody($container, '', script: '/image-test-status');
        self::assertMatchesRegularExpression('/^max active processes:\s+8$/m', $status, 'FPM_PM_MAX_CHILDREN');
        self::assertMatchesRegularExpression('/^max children reached:\s+[1-9]/m', $status, 'FPM_PM_MAX_CHILDREN');
        $this->waitFor(function () use ($container): void {
            self::assertSame(4, $this->phpFpmWorkers($container), 'idle workers once the requests ended');
        }, 10, 1);
    }

    /**
     * FPM_MAX_REQUESTS=2 and FPM_PROCESS_IDLE_TIMEOUT=5s, in a container of their own with fpm.pool.pm=ondemand and one
     * worker: a worker serves two requests, then a new one starts; an idle worker exits after 5 s. The 5 s leave time for
     * the requests, each a docker exec, before the worker is idle for too long.
     */
    public function testPhpFpmMaxRequestsAndIdleTimeoutFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('fpm-ondemand', [
            'fpm.pool.pm' => 'ondemand',
            'FPM_PM_MAX_CHILDREN' => '1',
            'FPM_MAX_REQUESTS' => '2',
            'FPM_PROCESS_IDLE_TIMEOUT' => '5s',
        ]);
        $processIds = array_map(function () use ($container): int {
            $process = json_decode($this->phpFpmBody($container, 'test=process'), true);
            self::assertIsArray($process);
            return $process['pid'];
        }, range(1, 3));
        self::assertSame($processIds[0], $processIds[1], 'the first two requests in one worker');
        self::assertNotSame($processIds[1], $processIds[2], 'a new worker after two requests');
        self::assertSame(1, $this->phpFpmWorkers($container));
        sleep(8);
        self::assertSame(0, $this->phpFpmWorkers($container), 'the idle worker exits after 5 s');
    }

    /**
     * In the container of the class, which has no CPU or memory limit, the process manager is sized to the CPUs and the
     * memory of the runner: pm.max_children is the smaller of the workers of 128 MB that fit into the memory beside
     * OPcache (256 MB) and 256 MB for the other processes, and of four per CPU, at least two; the spare servers follow
     * the CPUs, and pm.start_servers lies in between. The container log names the CPUs, the memory and the values. A
     * worker ends after 1000 requests.
     */
    public function testPhpFpmPoolIsSizedToTheContainer(): void
    {
        $container = self::container();
        [$cpus, $memory] = $this->containerResources($container);
        $children = max(2, min(intdiv($memory - 256 - 256, 128), 4 * $cpus));
        $minSpare = max(1, min(intdiv($cpus, 2), $children));
        $maxSpare = max($minSpare, min($children, 2 * $cpus));
        $expected = [$children, $minSpare + intdiv($maxSpare - $minSpare, 2), $minSpare, $maxSpare];

        $configuration = $this->assertCommandSucceeds('php-fpm -tt')->stderr;
        self::assertSame($expected, self::phpFpmProcessManager($configuration), sprintf('%d CPUs, %d MB memory', $cpus, $memory));
        self::assertMatchesRegularExpression('/NOTICE: \tpm\.max_requests = 1000$/m', $configuration);
        self::assertMatchesRegularExpression(vsprintf(
            '/^PHP-FPM: %d CPUs \([^)]+\), %d MB memory \([^)]+\) -> pm\.max_children=%d, pm\.start_servers=%d, pm\.min_spare_servers=%d, pm\.max_spare_servers=%d$/m',
            [$cpus, $memory, ...$expected],
        ), $container->logs());
    }

    /**
     * Settings of the sizing, see testPhpFpmPoolIsSizedToTheLimitsOfTheContainer(): the environment, a pool file
     * www.conf of your own if not empty, and pm.max_children, pm.start_servers, pm.min_spare_servers and
     * pm.max_spare_servers.
     *
     * @return iterable<string, array{array<string, string>, string, list<int>}>
     */
    public static function phpFpmPoolSizes(): iterable
    {
        yield 'the limits' => [[], '', [4, 2, 1, 4]];
        yield 'FPM_PM_MAX_CHILDREN' => [['FPM_PM_MAX_CHILDREN' => '7'], '', [7, 2, 1, 4]];
        yield 'fpm.pool.pm.max_children' => [['fpm.pool.pm.max_children' => '3'], '', [3, 2, 1, 3]];
        yield 'FPM_AUTO_CHILD_MEMORY and PHP_OPCACHE_MEMORY_CONSUMPTION' => [['FPM_AUTO_CHILD_MEMORY' => '64M', 'PHP_OPCACHE_MEMORY_CONSUMPTION' => '384'], '', [6, 2, 1, 4]];
        yield 'FPM_AUTO_CHILDREN_PER_CPU' => [['FPM_AUTO_CHILDREN_PER_CPU' => '1'], '', [2, 1, 1, 2]];
        yield 'FPM_AUTO_SIZE' => [['FPM_AUTO_SIZE' => '0'], '', [5, 2, 1, 3]];
        yield 'a pool file of your own' => [[], "[www]\npm.max_children = 20\n", [20, 2, 1, 3]];
    }

    /**
     * With --cpus 2 --memory 1g, in short runs of php-fpm -tt: (1024 MB - 256 MB of OPcache - 256 MB) / 128 MB give
     * four workers, two CPUs eight, so pm.max_children is 4, pm.min_spare_servers 1 (half the CPUs),
     * pm.max_spare_servers 4 (two per CPU, at most pm.max_children) and pm.start_servers 2. A value that is set stays,
     * and the others fit it: with seven workers, still at most four spare ones; with three, three.
     * FPM_AUTO_CHILD_MEMORY=64M and PHP_OPCACHE_MEMORY_CONSUMPTION=384 give (1024 - 384 - 256) / 64 = 6 workers,
     * FPM_AUTO_CHILDREN_PER_CPU=1 two. FPM_AUTO_SIZE=0 and a pool file of your own keep the values of the pool files.
     * The configuration test succeeds.
     *
     * @param array<string, string> $environment
     * @param list<int> $expected
     */
    #[DataProvider('phpFpmPoolSizes')]
    public function testPhpFpmPoolIsSizedToTheLimitsOfTheContainer(array $environment, string $poolFile, array $expected): void
    {
        // the pool file has to be there when the entrypoint sizes the pool, so the command runs the entrypoint again
        $command = $poolFile === '' ? ['php-fpm', '-tt'] : [
            'bash', '-c', 'printf %s ' . escapeshellarg($poolFile) . ' > /opt/docker/etc/php/fpm/pool.d/www.conf && exec /entrypoint php-fpm -tt',
        ];
        $result = $this->runContainer($command, $environment, ['--cpus', '2', '--memory', '1g']);
        self::assertSame(0, $result->exitCode, $result->describe());
        self::assertSame($expected, self::phpFpmProcessManager($result->stderr));
    }

    /**
     * Sends $requests requests that sleep for $seconds to PHP-FPM at once, and returns the largest number of workers
     * seen until $within seconds after they started. Waits until the requests ended.
     */
    private function phpFpmWorkersDuring(Container $container, int $requests, int $seconds, float $within): int
    {
        $this->assertCommandSucceeds(sprintf(
            'for request in $(seq %d); do (%s < /dev/null > /dev/null 2>&1 &); done',
            $requests,
            self::phpFpmCommand('test=sleep&time_limit=0&seconds=' . $seconds),
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

    /**
     * The CPUs and the memory in MB of $container as the entrypoint sees them: the CPUs it may run on and the memory of
     * the host, or the limits of cgroup v2 or v1 where they are lower. A CPU limit is rounded up.
     *
     * @return array{int, int}
     */
    private function containerResources(Container $container): array
    {
        $lines = explode("\n", trim($this->stdout(
            "nproc; awk '/^MemTotal:/ { print \$2 }' /proc/meminfo;"
            . ' cat /sys/fs/cgroup/cpu.max 2>/dev/null || echo "$(cat /sys/fs/cgroup/cpu/cpu.cfs_quota_us) $(cat /sys/fs/cgroup/cpu/cpu.cfs_period_us)";'
            . ' cat /sys/fs/cgroup/memory.max 2>/dev/null || cat /sys/fs/cgroup/memory/memory.limit_in_bytes',
            $container,
        )));
        self::assertCount(4, $lines, implode("\n", $lines));
        $cpus = (int) $lines[0];
        $memory = intdiv((int) $lines[1], 1024);
        [$quota, $period] = explode(' ', $lines[2]) + ['', ''];
        if (ctype_digit($quota) && ctype_digit($period) && (int) $period > 0) {
            $cpus = min($cpus, intdiv((int) $quota + (int) $period - 1, (int) $period));
        }

        if (ctype_digit($lines[3])) {
            $memory = min($memory, intdiv((int) $lines[3], 1048576));
        }

        return [$cpus, $memory];
    }

    /**
     * pm.max_children, pm.start_servers, pm.min_spare_servers and pm.max_spare_servers in the output of php-fpm -tt.
     *
     * @return list<int>
     */
    private static function phpFpmProcessManager(string $configuration): array
    {
        return array_map(static function (string $directive) use ($configuration): int {
            if (preg_match('/NOTICE: \t' . preg_quote($directive, '/') . ' = (\d+)$/m', $configuration, $matches) !== 1) {
                self::fail($directive . ' should be in the configuration: ' . $configuration);
            }

            return (int) $matches[1];
        }, ['pm.max_children', 'pm.start_servers', 'pm.min_spare_servers', 'pm.max_spare_servers']);
    }
}
