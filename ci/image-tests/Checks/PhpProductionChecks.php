<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * Checks of the PHP images without -dev: no debugger, and the OPcache settings for production.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpProductionChecks
{
    use PhpChecks;

    public function testNoDebuggerIsLoaded(): void
    {
        $modules = $this->lines($this->stdout('php -m'));
        self::assertNotContains('xdebug', $modules);
        self::assertNotContains('blackfire', $modules);
    }

    /**
     * Postfix does not run, so mail() fails, see docs/images/php.md#mail.
     */
    public function testMailFailsWithoutPostfix(): void
    {
        self::assertSame('not sent', $this->stdout(
            "php -r 'echo mail(\"someone@example.test\", \"Image test\", \"Sent by the image tests\") ? \"sent\" : \"not sent\";' 2>/dev/null",
        ));
    }

    public function testOpcacheSettingsForProduction(): void
    {
        self::assertSame([
            'opcache.memory_consumption' => '256',
            'opcache.interned_strings_buffer' => '16',
            'opcache.max_accelerated_files' => '7963',
        ], $this->phpSettings(['opcache.memory_consumption', 'opcache.interned_strings_buffer', 'opcache.max_accelerated_files']));
    }
}
