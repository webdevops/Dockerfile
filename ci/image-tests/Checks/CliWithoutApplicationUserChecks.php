<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The cli command in the images without APPLICATION_USER, where it fails, see docs/known-issues.md. Once it works
 * there, this check and the entry in the known issues go.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait CliWithoutApplicationUserChecks
{
    public function testCliFailsWithoutApplicationUser(): void
    {
        $result = $this->runContainer(['cli'], ['CLI_SCRIPT' => 'id -un']);

        self::assertNotSame(0, $result->exitCode, $result->describe());
        self::assertStringContainsString('APPLICATION_USER: unbound variable', $result->stderr);
    }
}
