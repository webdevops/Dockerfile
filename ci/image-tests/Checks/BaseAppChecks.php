<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function explode;
use function trim;

/**
 * Checks of the images based on webdevops/base-app: the application user, locales and moreutils.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait BaseAppChecks
{
    public function testLocalesAreGenerated(): void
    {
        if (!self::image()->isDebianOrUbuntu()) {
            self::markTestSkipped('The locales are only generated in the Debian and Ubuntu images.');
        }

        $locales = $this->stdout('locale -a');
        foreach (['C', 'POSIX', 'en_US.utf8', 'de_DE.utf8', 'de_LU', 'es_BO', 'st_ZA', 'zu_ZA', 'ca_ES', 'fr_FR'] as $locale) {
            self::assertStringContainsString($locale, $locales);
        }
    }

    /**
     * APPLICATION_PATH: /app exists and belongs to the application user.
     */
    public function testApplicationPathBelongsToTheApplicationUser(): void
    {
        $file = $this->file('/app');
        self::assertTrue($file->isDirectory, '/app should be a directory');
        self::assertSame('application', $file->owner);
        self::assertSame('application', $file->group);
    }

    public function testApplicationUser(): void
    {
        $this->assertCommandSucceeds('id application');
        self::assertContains('application', explode(' ', trim($this->stdout('id -Gn application'))), 'application should belong to the group application');
        self::assertSame('1000', trim($this->stdout('id -u application')));
        // APPLICATION_GID
        self::assertSame('1000', trim($this->stdout('id -g application')));

        $entry = explode(':', trim($this->stdout('getent passwd application')));
        self::assertSame('/home/application', $entry[5] ?? null, 'home directory of application');
        self::assertSame('/bin/bash', $entry[6] ?? null, 'login shell of application');
    }

    /**
     * The cli command runs CLI_SCRIPT with the further arguments as the application user, see docs/customization.md.
     */
    public function testCliRunsTheCliScriptAsTheApplicationUser(): void
    {
        $result = $this->runContainer(['cli', '-un'], ['CLI_SCRIPT' => 'id']);
        self::assertSame([0, "application\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testMoreutils(): void
    {
        if (!self::image()->isDebianOrUbuntu()) {
            self::markTestSkipped('moreutils is only installed in the Debian and Ubuntu images.');
        }

        self::assertSame("/usr/bin/sponge\n", $this->stdout('command -v sponge'));
    }
}
