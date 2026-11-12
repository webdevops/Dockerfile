<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Checks of the -dev PHP images: Xdebug, but not Blackfire, and the settings for development.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpDevelopmentChecks
{
    use PhpChecks;

    public function testXdebugIsLoaded(): void
    {
        $result = $this->assertCommandSucceeds('php -m');
        self::assertSame('', $result->stderr);
        $modules = $this->lines($result->stdout);
        self::assertContains('xdebug', $modules);
        self::assertNotContains('blackfire', $modules);
    }

    public function testSettingsForDevelopment(): void
    {
        self::assertSame([
            'xdebug.mode' => 'debug',
            'xdebug.start_with_request' => 'trigger',
            'opcache.memory_consumption' => '256',
            'opcache.revalidate_freq' => '0',
            'opcache.validate_timestamps' => '1',
            'opcache.interned_strings_buffer' => '16',
            'opcache.max_accelerated_files' => '7963',
        ], $this->phpSettings([
            'xdebug.mode', 'xdebug.start_with_request', 'opcache.memory_consumption', 'opcache.revalidate_freq',
            'opcache.validate_timestamps', 'opcache.interned_strings_buffer', 'opcache.max_accelerated_files',
        ]));
    }
}
