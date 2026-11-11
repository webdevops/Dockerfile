<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * TZ, LOG_STDOUT and LOG_STDERR in the environment container, see docs/environment-variables.md#container.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait ContainerEnvironmentChecks
{
    public function testTimeZoneFromTheEnvironment(): void
    {
        self::assertContains(trim($this->stdout('date +%Z', $this->environmentContainer())), ['CET', 'CEST'], 'TZ=Europe/Berlin');
    }

    public function testLogFilesFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();

        self::assertSame(static::ENVIRONMENT['LOG_STDOUT'] . "\n", $this->stdout('readlink /docker.stdout', $container));
        self::assertSame(static::ENVIRONMENT['LOG_STDERR'] . "\n", $this->stdout('readlink /docker.stderr', $container));
    }
}
