<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * PHP_DEBUGGER, XDEBUG_* and BLACKFIRE_* in the development images, see
 * docs/environment-variables.md#development-images.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpDevelopmentEnvironmentChecks
{
    public function testXdebugSettingsFromTheEnvironment(): void
    {
        $settings = [
            'xdebug.mode' => 'XDEBUG_MODE',
            'xdebug.start_with_request' => 'XDEBUG_START_WITH_REQUEST',
            'xdebug.client_host' => 'XDEBUG_CLIENT_HOST',
            'xdebug.client_port' => 'XDEBUG_CLIENT_PORT',
            'xdebug.discover_client_host' => 'XDEBUG_DISCOVER_CLIENT_HOST',
            'xdebug.trigger_value' => 'XDEBUG_TRIGGER_VALUE',
            'xdebug.output_dir' => 'XDEBUG_OUTPUT_DIR',
        ];
        $script = sprintf('echo json_encode(array_map(ini_get(...), %s));', var_export(array_combine(array_keys($settings), array_keys($settings)), true));

        self::assertSame(
            array_map(static fn (string $variable): string => static::ENVIRONMENT[$variable], $settings),
            json_decode($this->stdout('php -r ' . escapeshellarg($script), $this->environmentContainer()), true),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function debuggers(): iterable
    {
        yield 'none' => ['none', [], ['xdebug', 'blackfire']];
        yield 'blackfire' => ['blackfire', ['blackfire'], ['xdebug']];
        yield 'xdebug' => ['xdebug', ['xdebug'], ['blackfire']];
    }

    /**
     * @param list<string> $loaded
     * @param list<string> $missing
     */
    #[DataProvider('debuggers')]
    public function testPhpDebuggerSelectsTheExtension(string $debugger, array $loaded, array $missing): void
    {
        $result = $this->runContainer(
            ['php', '-r', 'echo json_encode([get_loaded_extensions(true), get_loaded_extensions(), ini_get("blackfire.server_id"), ini_get("blackfire.server_token")]);'],
            ['PHP_DEBUGGER' => $debugger, 'BLACKFIRE_SERVER_ID' => 'image-test-id', 'BLACKFIRE_SERVER_TOKEN' => 'image-test-token'],
        );
        self::assertSame(0, $result->exitCode, $result->describe());
        $values = json_decode($result->stdout, true);
        self::assertIsArray($values, $result->describe());
        [$zendExtensions, $extensions, $serverId, $serverToken] = $values;
        $names = array_map(strtolower(...), [...$zendExtensions, ...$extensions]);

        foreach ($loaded as $extension) {
            self::assertContains($extension, $names, sprintf('PHP_DEBUGGER=%s should load %s', $debugger, $extension));
        }

        foreach ($missing as $extension) {
            self::assertNotContains($extension, $names, sprintf('PHP_DEBUGGER=%s should not load %s', $debugger, $extension));
        }

        if ($debugger === 'blackfire') {
            self::assertSame(['image-test-id', 'image-test-token'], [$serverId, $serverToken], 'BLACKFIRE_SERVER_ID and BLACKFIRE_SERVER_TOKEN');
        }
    }
}
