<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Symfony\Component\Process\Process;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function json_decode;
use function sprintf;
use function str_replace;
use function trim;

/**
 * docker-healthcheck, which checks the services of supervisord, and the HEALTHCHECK of the PHP images from PHP 8.6 on,
 * see docs/customization.md#healthcheck. The programs of the tests are added to supervisord in test containers of
 * their own.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait HealthcheckChecks
{
    public function testHealthcheckIsHealthyWhileTheServicesRun(): void
    {
        $this->eventually(function (): void {
            $this->assertCommandSucceeds('docker-healthcheck');
        });
    }

    /**
     * A service that supervisord gives up on (FATAL) makes the container unhealthy, and the command names it.
     */
    public function testHealthcheckFailsWhenAServiceFails(): void
    {
        $container = $this->startTestContainer('healthcheck-failing');
        $this->addProgram($container, 'image-test-failing', "command=/bin/false\nautostart=true\nautorestart=false\nstartsecs=1\nstartretries=0");

        $this->eventually(function () use ($container): void {
            $result = $this->execute('docker-healthcheck', $container);
            self::assertSame(1, $result->exitCode, $result->describe());
            self::assertMatchesRegularExpression('/^image-test-failing\s+FATAL\b/m', $result->stdout);
        });

        // the reason is in the container log too, and the first check that passes again says so there
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('/^docker-healthcheck: unhealthy: image-test-failing\s+FATAL\b/m', $this->containerLog($container));
        });
        $this->addProgram($container, 'image-test-failing', "command=sleep 3600\nautostart=true");
        $this->eventually(function () use ($container): void {
            $this->assertCommandSucceeds('docker-healthcheck', $container);
        });
        $this->eventually(function () use ($container): void {
            self::assertMatchesRegularExpression('/^docker-healthcheck: healthy again$/m', $this->containerLog($container));
        });
    }

    /**
     * A stopped service is not a failure: supervisorctl status ends with an error then, docker-healthcheck does not.
     */
    public function testHealthcheckIgnoresAStoppedService(): void
    {
        $container = $this->startTestContainer('healthcheck-stopped');
        $this->addProgram($container, 'image-test-sleeping', "command=sleep 3600\nautostart=true\nautorestart=true");
        $this->eventually(function () use ($container): void {
            self::assertStringContainsString(' RUNNING ', $this->stdout('supervisorctl status image-test-sleeping || true', $container));
        });
        $this->assertCommandSucceeds('supervisorctl stop image-test-sleeping', $container);

        self::assertNotSame(0, $this->execute('supervisorctl status', $container)->exitCode, 'supervisorctl status fails with a stopped service');
        $this->assertCommandSucceeds('docker-healthcheck', $container);
    }

    /**
     * From PHP 8.6 on, the PHP images run docker-healthcheck as their HEALTHCHECK and Docker reports them as healthy;
     * the older PHP images and the base images have no HEALTHCHECK.
     */
    public function testHealthcheckOfTheImage(): void
    {
        $healthcheck = json_decode($this->docker(['image', 'inspect', '--format', '{{json .Config.Healthcheck}}', self::image()->reference]), true);
        if (!self::image()->hasHealthcheck()) {
            self::assertNull($healthcheck);
            return;
        }

        self::assertSame([
            'Test' => ['CMD', 'docker-healthcheck'],
            'Interval' => 30_000_000_000,
            'Timeout' => 5_000_000_000,
            'StartPeriod' => 30_000_000_000,
            'Retries' => 3,
        ], $healthcheck);
        $this->eventually(function (): void {
            self::assertSame('healthy', $this->docker(['inspect', '--format', '{{.State.Health.Status}}', self::container()->id]));
        });
    }

    private function addProgram(Container $container, string $name, string $settings): void
    {
        $this->assertCommandSucceeds(sprintf(
            "printf '[program:%s]\\n%s\\n' > /opt/docker/etc/supervisor.d/%s.conf && supervisorctl update",
            $name,
            str_replace("\n", '\\n', $settings),
            $name,
        ), $container);
    }

    /**
     * What docker logs shows of $container: its stdout and stderr.
     */
    private function containerLog(Container $container): string
    {
        $process = new Process(['docker', 'logs', $container->id]);
        $process->mustRun();
        return $process->getOutput() . $process->getErrorOutput();
    }

    /**
     * @param list<string> $arguments
     */
    private function docker(array $arguments): string
    {
        $process = new Process(['docker', ...$arguments]);
        $process->mustRun();
        return trim($process->getOutput());
    }
}
