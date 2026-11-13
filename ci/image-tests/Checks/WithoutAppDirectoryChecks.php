<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function in_array;

/**
 * webdevops/nginx and webdevops/apache have no /app, the default document root, see docs/known-issues.md. Without a
 * volume or files of your own in /app, Nginx answers 500 and Apache does not start. The container of this check gets
 * no files of the tests, which would create /app. Once the images have /app, this check and the known issue go.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait WithoutAppDirectoryChecks
{
    public function testWebServerWithoutAppDirectory(): void
    {
        $container = $this->startTestContainer('without-app', withFiles: false);
        self::assertNotSame(0, $this->execute('test -e /app', $container)->exitCode, '/app should not exist');
        if (in_array('apache', self::services(), true)) {
            $this->eventually(static function () use ($container): void {
                self::assertStringContainsString("DocumentRoot '/app' is not a directory", $container->logs());
            });
            self::assertNotSame(0, $this->execute('curl --silent http://localhost/', $container)->exitCode, 'Apache should not answer');
            return;
        }

        // Nginx falls back to the index file, which is missing too, and ends in a loop
        $this->eventually(function () use ($container): void {
            self::assertSame('500', $this->statusCode('http://localhost/', container: $container));
            self::assertStringContainsString('rewrite or internal redirection cycle', $container->logs());
        });
    }
}
