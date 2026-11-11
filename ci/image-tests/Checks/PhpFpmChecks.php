<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Checks of the PHP-FPM configuration in every PHP image.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpFpmChecks
{
    public function testPhpFpmLayout(): void
    {
        foreach (['/opt/docker/etc/php', '/opt/docker/etc/php/fpm', '/opt/docker/etc/php/fpm/pool.d'] as $path) {
            self::assertTrue($this->file($path)->isDirectory, $path . ' should be a directory');
        }

        foreach (['/opt/docker/etc/php/php.ini', '/opt/docker/etc/php/fpm/php-fpm.conf', '/opt/docker/etc/php/fpm/pool.d/application.conf'] as $path) {
            self::assertTrue($this->file($path)->isFile, $path . ' should be a file');
        }
    }

    public function testPoolDirectoryOfTheOfficialImageIsALink(): void
    {
        if (!self::image()->phpOfficial) {
            self::markTestSkipped('Only the images based on the official PHP image have /usr/local/etc/php-fpm.d.');
        }

        self::assertTrue($this->file('/usr/local/etc/php-fpm.d')->isSymlink, '/usr/local/etc/php-fpm.d should be a symbolic link');
    }

    public function testPhpFpmDisablesUploadProgress(): void
    {
        self::assertMatchesRegularExpression('/^session\.upload_progress\.enabled => Off => Off$/m', $this->stdout('php-fpm -i'));
    }
}
