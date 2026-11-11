<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

use function explode;
use function trim;

/**
 * The tools that the CI and the make targets of CONTRIBUTING.md need in webdevops/dockerfile-build-env.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait BuildEnvChecks
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function buildTools(): iterable
    {
        yield 'make' => ['make --version', '/\AGNU Make \d+\.\d+/'];
        yield 'docker' => ['docker --version', '/\ADocker version \d+\.\d+\.\d+/'];
        yield 'container-structure-test' => ['container-structure-test version', '/\Av\d+\.\d+\.\d+$/m'];
        yield 'git' => ['git --version', '/\Agit version \d+\.\d+/'];
    }

    #[DataProvider('buildTools')]
    public function testBuildToolRuns(string $command, string $pattern): void
    {
        self::assertMatchesRegularExpression($pattern, $this->stdout($command));
    }

    public function testApplicationUserMayUseDocker(): void
    {
        self::assertContains('docker', explode(' ', trim($this->stdout('id -nG application'))));
    }
}
