<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_combine;
use function array_keys;
use function in_array;
use function preg_grep;
use function preg_quote;
use function ksort;
use function preg_match_all;
use function sleep;
use function sort;
use function sprintf;

/**
 * The services that supervisord starts, the hooks they source and their options, see docs/customization.md and
 * docs/environment-variables.md. The test class names its services in SERVICES. Hooks/ adds a hook to the hook
 * directory of each service that marks that it ran.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ServiceChecks
{
    /**
     * The hook directory in /opt/docker/bin/service.d of each service.
     */
    private const array HOOK_DIRECTORIES = [
        'apache' => 'httpd.d',
        'cron' => 'cron.d',
        'dnsmasq' => 'dnsmasq.d',
        'nginx' => 'nginx.d',
        'php-fpm' => 'php-fpm.d',
        'postfix' => 'postfix.d',
        'ssh' => 'ssh.d',
        'syslog' => 'syslog-ng.d',
    ];

    /**
     * The services run and keep their processes: supervisord restarts a crashed service at once and shows it as RUNNING
     * again, but with another process.
     */
    public function testSupervisordStartsTheServicesOfTheImage(): void
    {
        $expected = self::services();
        sort($expected);
        $this->eventually(function () use ($expected): void {
            self::assertSame($expected, array_keys($this->runningServices()));
        });

        $processes = $this->runningServices();
        sleep(self::WAIT_SECONDS * 2);
        self::assertSame($processes, $this->runningServices(), 'The services should keep their processes; a new process id means a restart.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hookDirectories(): iterable
    {
        yield 'supervisord' => ['supervisor.d'];
        foreach (self::services() as $service) {
            yield $service => [self::HOOK_DIRECTORIES[$service] ?? throw new LogicException(sprintf('ServiceChecks knows no hook directory of the service %s.', $service))];
        }
    }

    /**
     * In the environment container, which has the hooks of Hooks/.
     */
    #[DataProvider('hookDirectories')]
    public function testServiceSourcesItsHooksAsRoot(string $directory): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($directory, $container): void {
            self::assertSame("root\n", $this->stdout('cat /tmp/image-tests/hooks/' . $directory . ' 2>&1 || true', $container));
        });
    }

    /**
     * The services whose SERVICE_*_OPTS the environment container sets, see ENVIRONMENT. Nginx gets a container of its
     * own, see NginxEnvironmentChecks.
     *
     * @return iterable<string, array{string}>
     */
    public static function serviceOptions(): iterable
    {
        yield 'supervisord' => ['supervisord'];
        foreach (self::services() as $service) {
            if (in_array($service, ['cron', 'syslog', 'ssh', 'apache', 'php-fpm'], true)) {
                yield $service => [$service];
            }
        }
    }

    /**
     * What the SERVICE_*_OPTS of the environment container do: supervisord logs at the level "trace"; syslog-ng writes
     * its process id to the file of --pidfile; sshd listens on port 2222; Apache defines IMAGE_TEST, for which the
     * configuration from Hooks/ sends a header; PHP-FPM names the session cookie IMAGETEST. Cron gets a log level of
     * its own, which shows only in its command line.
     */
    #[DataProvider('serviceOptions')]
    public function testServiceOptionsFromTheEnvironment(string $service): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($container, $service): void {
            match ($service) {
                'supervisord' => self::assertMatchesRegularExpression(
                    '/^\S+ \S+ TRAC XML-RPC method called: supervisor\./m',
                    $this->containerLogAfterSupervisorctl($container),
                    'SERVICE_SUPERVISOR_OPTS=--loglevel=trace',
                ),
                'syslog' => self::assertMatchesRegularExpression(
                    '/^syslog-ng /',
                    $this->stdout('tr "\\000" " " < /proc/$(cat /tmp/image-test-syslog-ng.pid)/cmdline 2>&1 || true', $container),
                    'SERVICE_SYSLOG_OPTS=--pidfile=/tmp/image-test-syslog-ng.pid',
                ),
                'ssh' => self::assertTrue(ListeningSockets::read($container)->isListening(2222), 'SERVICE_SSH_OPTS=-p 2222'),
                'apache' => self::assertMatchesRegularExpression(
                    '/^X-Image-Test-Define: IMAGE_TEST\r?$/mi',
                    $this->stdout('curl --silent --head http://localhost/', $container),
                    'SERVICE_APACHE_OPTS=-DIMAGE_TEST',
                ),
                'php-fpm' => self::assertSame('IMAGETEST', $this->phpFpmBody($container, 'test=session_name'), 'SERVICE_PHPFPM_OPTS=-dsession.name=IMAGETEST'),
                'cron' => self::assertNotSame([], preg_grep(
                    '#^/usr/sbin/' . (self::image()->isAlpine() ? 'crond' : 'cron') . ' -f ' . preg_quote(self::cronOptions(), '#') . '$#',
                    $this->commandLines($container),
                ), 'SERVICE_CRON_OPTS'),
                default => throw new LogicException('No check of the options of ' . $service),
            };
        });
    }

    /**
     * The container log after a call of supervisorctl, which supervisord logs at the level "trace".
     */
    private function containerLogAfterSupervisorctl(Container $container): string
    {
        $this->execute('supervisorctl status', $container);
        return $container->logs();
    }

    /**
     * The groups that supervisorctl reports as RUNNING, with their process ids, sorted by group.
     *
     * @return array<string, string>
     */
    private function runningServices(?Container $container = null): array
    {
        // supervisorctl status exits with 3 when a program is stopped, which is normal here; but it must list programs,
        // or supervisord does not answer
        $result = $this->execute('supervisorctl status', $container);
        self::assertMatchesRegularExpression('/^[\w-]+:\S+\s+[A-Z]+/m', $result->stdout, $result->describe());
        preg_match_all('/^([\w-]+):\S+\s+RUNNING\s+pid (\d+)/m', $result->stdout, $matches);
        $services = array_combine($matches[1], $matches[2]);
        ksort($services);
        return $services;
    }
}
