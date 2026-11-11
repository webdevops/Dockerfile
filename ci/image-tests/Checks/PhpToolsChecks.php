<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Composer in the -dev PHP images.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpToolsChecks
{
    public function testComposerRuns(): void
    {
        $file = $this->file('/usr/local/bin/composer');
        self::assertTrue($file->isFile, '/usr/local/bin/composer should be a file');
        self::assertTrue($file->isExecutable, '/usr/local/bin/composer should be executable');
        $this->assertCommandSucceeds('/usr/local/bin/composer');
    }
}
