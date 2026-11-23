<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\ImageConfiguration;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_keys;
use function dirname;
use function implode;
use function preg_quote;
use function sprintf;

/**
 * Checks of every image: the layout and the tools of the bootstrap layer (template/Dockerfile/images/bootstrap.twig and
 * provisioning/baselayout) and the distribution.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait BootstrapChecks
{
    /**
     * @return array<string, array{string}>
     */
    public static function bootstrapDirectories(): array
    {
        return self::cases([
            '/usr',
            '/usr/local',
            '/usr/local/bin',
        ]);
    }

    #[DataProvider('bootstrapDirectories')]
    public function testBootstrapDirectory(string $path): void
    {
        $this->assertRootDirectory($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bootstrapScripts(): array
    {
        return self::cases([
            '/usr/local/bin/apk-install',
            '/usr/local/bin/apk-upgrade',
            '/usr/local/bin/apt-install',
            '/usr/local/bin/apt-upgrade',
        ]);
    }

    #[DataProvider('bootstrapScripts')]
    public function testBootstrapScript(string $path): void
    {
        $this->assertRootScript($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function devices(): array
    {
        return self::cases([
            '/dev/zero',
            '/dev/null',
            '/dev/random',
            '/dev/urandom',
            '/dev/tty',
            '/dev/full',
        ]);
    }

    #[DataProvider('devices')]
    public function testDevice(string $path): void
    {
        $file = $this->file($path);
        self::assertTrue($file->isCharacterDevice, $path . ' should be a character device');
        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, ['owner' => 'rwX', 'group' => 'rwX', 'others' => 'rwX']);
    }

    public function testDistribution(): void
    {
        $image = self::image();
        [$family, $distribution] = match ($image->osFamily) {
            'alpine' => ['Alpine', 'Alpine'],
            'debian' => ['Debian', 'Debian'],
            'ubuntu' => ['Debian', 'Ubuntu'],
            default => self::fail(sprintf('No distribution checks for OS family "%s".', $image->osFamily)),
        };

        self::assertStringContainsString($family, $this->stdout('docker-image-info family'));
        self::assertStringContainsString($distribution, $this->stdout('docker-image-info dist'));
        self::assertStringContainsString($image->osVersion, $this->stdout('docker-image-info dist-version'));

        // the version, then the end or a dot as in 3.24.3; any release if the version is not set
        $release = $image->osVersion === '' ? '\S' : preg_quote($image->osVersion, '/') . '(\.|\s|$)';
        if ($image->isAlpine()) {
            self::assertMatchesRegularExpression('/Release: ' . $release . '/', $this->stdout('sed -e "s/^/Release: /" /etc/alpine-release'));
            return;
        }

        $lsb = $this->stdout('docker-image-info lsb');
        self::assertStringContainsString("Distributor ID:\t" . $distribution, $lsb);
        self::assertMatchesRegularExpression('/Release:\t' . $release . '/', $lsb);

        $codename = $this->stdout('docker-image-info dist-codename');
        self::assertSame($this->stdout('. /etc/os-release && echo "$VERSION_CODENAME"'), $codename);
        self::assertMatchesRegularExpression('/\A[a-z]+\n\z/', $codename);
    }

    public function testGoReplace(): void
    {
        self::assertMatchesRegularExpression('/go-replace\s+(version)?\s*[0-9]+\.[0-9]+\.[0-9]+/', $this->stdout('go-replace --version'));
    }

    public function testDockerImageCleanup(): void
    {
        // in a container of its own, as the command empties /tmp and /var/log
        $cache = self::image()->isAlpine() ? '/var/cache/apk/image-test' : '/var/lib/apt/lists/image-test';
        $result = $this->runContainer(['sh', '-c', implode(' && ', [
            'mkdir -p ' . dirname($cache),
            'touch /tmp/image-test ' . $cache,
            'docker-image-cleanup',
            'test ! -e /tmp/image-test',
            'test ! -e ' . $cache,
            'echo cleaned',
        ])]);

        self::assertSame(0, $result->exitCode, $result->describe());
        self::assertStringEndsWith("cleaned\n", $result->stdout);
    }

    public function testApkInstallsFromTheCache(): void
    {
        if (!self::image()->isAlpine()) {
            self::markTestSkipped('apk is only in the Alpine images.');
        }

        $result = $this->assertCommandSucceeds('apk add --update-cache --no-network bash');
        self::assertStringNotContainsString('ERROR', $result->stdout);
        self::assertStringNotContainsString('unsatisfiable constraints', $result->stdout);
    }

    /**
     * The variables of the locale and the terminal that the images set, and the PATH of the official PHP image.
     */
    public function testLocaleTerminalAndPathOfTheImage(): void
    {
        $expected = [
            'LC_ALL' => 'C.UTF-8',
            'LANG' => 'C.UTF-8',
            'TERM' => 'xterm',
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ];
        self::assertSame($expected, ImageConfiguration::inspect(self::image()->reference)->variables(array_keys($expected)));
    }

    /**
     * The commands from provisioning/baselayout, which every image copies from webdevops/toolbox.
     *
     * @return array<string, array{string}>
     */
    public static function bootstrapCommands(): array
    {
        return self::cases([
            '/usr/local/bin/apk-install',
            '/usr/local/bin/apk-upgrade',
            '/usr/local/bin/apt-add-repository',
            '/usr/local/bin/apt-install',
            '/usr/local/bin/apt-update',
            '/usr/local/bin/apt-upgrade',
            '/usr/local/bin/docker-image-cleanup',
            '/usr/local/bin/docker-image-info',
            '/usr/local/bin/generate-locales',
            '/usr/local/bin/generate-dockerimage-info',
            '/usr/local/bin/docker-run-bootstrap',
            '/usr/local/bin/go-replace',
        ]);
    }

    /**
     * Programs of root with exactly the mode 0755: no setuid, setgid or sticky bit.
     */
    #[DataProvider('bootstrapCommands')]
    public function testBootstrapCommandPermissions(string $path): void
    {
        $this->assertRootScript($path);
        self::assertSame('-rwxr-xr-x', $this->file($path)->permissions(), $path);
    }

    /**
     * gosu, with which the entrypoint runs commands as another user, is a program of root with the mode 0755 in the
     * running container. The images build it with the setuid and setgid bits, so that their entrypoint can switch to
     * root when the container starts as another user, and the entrypoint removes them.
     */
    public function testGosuPermissions(): void
    {
        $gosu = $this->file('/sbin/gosu');
        $this->assertOwnedByRoot($gosu);
        self::assertSame('-rwxr-xr-x', $gosu->permissions());
    }
}
