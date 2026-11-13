<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_filter;
use function array_values;
use function str_contains;
use function trim;

/**
 * The commands of the entrypoint and the scripts in /entrypoint.d, see docs/customization.md. Hooks/ adds a script to
 * /entrypoint.d and the command image-test to /opt/docker/bin/entrypoint.d.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait EntrypointChecks
{
    public function testEntrypointSourcesEntrypointDBeforeEveryCommand(): void
    {
        self::assertSame("root\n", $this->stdout('cat /tmp/image-tests/entrypoint.d', $this->environmentContainer()), 'with supervisord');

        $result = $this->runContainer(['cat', '/tmp/image-tests/entrypoint.d']);
        self::assertSame([0, "root\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testOtherCommandsRunAsRoot(): void
    {
        $result = $this->runContainer(['id', '-un']);
        self::assertSame([0, "root\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testRootCommandRunsItsArgumentsAsRoot(): void
    {
        $result = $this->runContainer(['root', 'id', '-un']);
        self::assertSame([0, "root\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testCommandOfItsOwnFromEntrypointD(): void
    {
        $result = $this->runContainer(['image-test', 'first', 'second']);
        self::assertSame([0, "image-test as root: first second\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testNoopKeepsTheContainerRunningWithoutServices(): void
    {
        $container = $this->startTestContainer('noop', [], ['noop']);

        $this->eventually(function () use ($container): void {
            self::assertSame('tail -f /dev/null', $this->commandLineOfProcess1($container));
        });
        self::assertTrue($container->isRunning());
        self::assertSame([], array_values(array_filter(
            $this->commandLines($container),
            static fn (string $commandLine): bool => str_contains($commandLine, 'supervisord'),
        )), 'supervisord does not run');
    }

    private function commandLineOfProcess1(Container $container): string
    {
        return trim($this->stdout('tr "\\000" " " < /proc/1/cmdline', $container));
    }
}
