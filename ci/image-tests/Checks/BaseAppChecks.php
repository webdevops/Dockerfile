<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\ImageConfiguration;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_keys;
use function escapeshellarg;
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

    /**
     * With APPLICATION_USER empty, the cli command runs CLI_SCRIPT as application, its default.
     */
    public function testCliRunsTheCliScriptAsApplicationWithEmptyApplicationUser(): void
    {
        $result = $this->runContainer(['cli', '-un'], ['CLI_SCRIPT' => 'id', 'APPLICATION_USER' => '']);
        self::assertSame([0, "application\n"], [$result->exitCode, $result->stdout], $result->describe());
    }

    public function testMoreutils(): void
    {
        if (!self::image()->isDebianOrUbuntu()) {
            self::markTestSkipped('moreutils is only installed in the Debian and Ubuntu images.');
        }

        self::assertSame("/usr/bin/sponge\n", $this->stdout('command -v sponge'));
    }

    public function testApplicationVariablesOfTheImage(): void
    {
        [$name, $id] = ['application', '1000'];
        $expected = [
            'APPLICATION_USER' => $name,
            'APPLICATION_GROUP' => $name,
            'APPLICATION_PATH' => '/app',
            'APPLICATION_UID' => $id,
            'APPLICATION_GID' => $id,
        ];
        self::assertSame($expected, ImageConfiguration::inspect(self::image()->reference)->variables(array_keys($expected)));
    }

    /**
     * The user application has the UID 1000, its primary group application the GID 1000.
     */
    public function testIdOfTheApplicationUser(): void
    {
        self::assertMatchesRegularExpression(
            '/^uid=1000\(application\) gid=1000\(application\) groups=1000\(application\)/',
            $this->stdout('id application'),
        );
    }

    /**
     * /app belongs to UID and GID 1000, with the mode 0755.
     */
    public function testApplicationPathPermissions(): void
    {
        $path = '/app';
        self::assertSame('drwxr-xr-x', $this->file($path)->permissions());
        self::assertSame("1000:1000\n", $this->stdout('stat -c %u:%g ' . $path));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function applicationTools(): array
    {
        return self::cases(['zip', 'unzip', 'bzip2', 'nslookup', 'ssh', 'rsync', 'git', 'patch']);
    }

    #[DataProvider('applicationTools')]
    public function testApplicationToolIsOnThePath(string $tool): void
    {
        self::assertSame('/usr/bin/' . $tool . "\n", $this->stdout('command -v ' . escapeshellarg($tool)));
    }

    /**
     * docker-run-bootstrap ran the scripts in /opt/docker/provision/bootstrap.d at the build and deleted them, e.g.
     * 10-user-application.sh and 20-app.sh, which create the user application and /app, and in the PHP images
     * 10-php-init.sh and 11-php-conf.sh.
     */
    public function testBuildScriptsWereRunAndDeleted(): void
    {
        self::assertSame('', $this->stdout("find /opt/docker/provision/bootstrap.d -name '*.sh'"));
    }
}
