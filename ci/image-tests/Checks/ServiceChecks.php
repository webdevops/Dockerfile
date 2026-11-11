<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function implode;
use function preg_grep;
use function preg_match_all;
use function sort;
use function sprintf;

/**
 * The services that supervisord starts, the hooks they source and their options, see docs/customization.md and
 * docs/environment-variables.md. The test class names its services in SERVICES. Fixtures/ adds a hook to the hook
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
        'nginx' => 'nginx.d',
        'php-fpm' => 'php-fpm.d',
        'postfix' => 'postfix.d',
        'ssh' => 'ssh.d',
        'syslog' => 'syslog-ng.d',
    ];

    /**
     * In the environment container: in the container of the class, other checks may start more services.
     */
    public function testSupervisordStartsTheServicesOfTheImage(): void
    {
        $expected = self::services();
        sort($expected);
        $container = $this->environmentContainer();
        $this->eventually(function () use ($expected, $container): void {
            self::assertSame($expected, $this->runningServices($container));
        });
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hookDirectories(): iterable
    {
        yield 'supervisord' => ['supervisor.d'];
        foreach (self::services() as $service) {
            yield $service => [self::HOOK_DIRECTORIES[$service]];
        }
    }

    #[DataProvider('hookDirectories')]
    public function testServiceSourcesItsHooksAsRoot(string $directory): void
    {
        $this->eventually(function () use ($directory): void {
            self::assertSame("root\n", $this->stdout('cat /tmp/image-tests/hooks/' . $directory . ' 2>&1 || true'));
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
            'cron' => ['SERVICE_CRON_OPTS', '#^/usr/sbin/(cron -f -L 15|crond -f -l 8)$#'],
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
     * The groups that supervisorctl reports as RUNNING, sorted.
     *
     * @return list<string>
     */
    private function runningServices(?Container $container = null): array
    {
        // supervisorctl status exits with 3 when a program is stopped, which is normal here
        $output = $this->execute('supervisorctl status', $container)->stdout;
        preg_match_all('/^([\w-]+):\S+\s+RUNNING\b/m', $output, $matches);
        $services = $matches[1];
        sort($services);
        return $services;
    }
}
