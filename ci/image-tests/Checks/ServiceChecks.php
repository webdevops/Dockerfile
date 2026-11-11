<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_combine;
use function array_keys;
use function implode;
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
     * The SERVICE_*_OPTS of a service end up as arguments of its program: the variable and a pattern of the command
     * line of the program with the value of the environment container.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function serviceOptions(): iterable
    {
        $programs = [
            'cron' => ['SERVICE_CRON_OPTS', '#^/usr/sbin/' . (self::image()->isAlpine() ? 'crond' : 'cron') . ' -f ' . preg_quote(self::cronOptions(), '#') . '$#'],
            'syslog' => ['SERVICE_SYSLOG_OPTS', '#^syslog-ng -F .* --stderr$#'],
            'ssh' => ['SERVICE_SSH_OPTS', '#^sshd: /usr/sbin/sshd -D -e( |$)#'],
            'nginx' => ['SERVICE_NGINX_OPTS', '#^nginx: master process /usr/sbin/nginx -g daemon off; -q$#'],
            'apache' => ['SERVICE_APACHE_OPTS', '#(apache2|httpd) -DFOREGROUND( .*)? -DIMAGE_TEST$#'],
        ];
        yield 'supervisord' => ['SERVICE_SUPERVISOR_OPTS', '#/supervisord -c /opt/docker/etc/supervisor\.conf .* --loglevel=info$#'];
        foreach (self::services() as $service) {
            if (isset($programs[$service])) {
                yield $service => $programs[$service];
            }
        }
    }

    #[DataProvider('serviceOptions')]
    public function testServiceOptionsFromTheEnvironment(string $variable, string $pattern): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($container, $variable, $pattern): void {
            $commandLines = $this->commandLines($container);
            self::assertNotSame([], preg_grep($pattern, $commandLines), sprintf(
                "%s should end up in the command line of its program; processes:\n%s",
                $variable,
                implode("\n", $commandLines),
            ));
        });
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
