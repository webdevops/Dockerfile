<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;
use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;
use function explode;
use function implode;
use function sleep;
use function sprintf;

/**
 * PHP_DEBUGGER, XDEBUG_* and BLACKFIRE_* in the development images, see
 * docs/environment-variables.md#development-images.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpDevelopmentEnvironmentChecks
{
    /**
     * XDEBUG_MODE=debug,trace, XDEBUG_START_WITH_REQUEST=yes, XDEBUG_CLIENT_HOST=127.0.0.2, XDEBUG_CLIENT_PORT=9010,
     * XDEBUG_DISCOVER_CLIENT_HOST=0 and XDEBUG_OUTPUT_DIR, in a container of their own: a PHP script, without a
     * trigger, connects to 127.0.0.2:9010, not to the address of X-Forwarded-For, and leaves a trace file.
     */
    public function testXdebugConnectsToTheClientFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('xdebug', [
            'XDEBUG_MODE' => 'debug,trace',
            'XDEBUG_START_WITH_REQUEST' => 'yes',
            'XDEBUG_CLIENT_HOST' => '127.0.0.2',
            'XDEBUG_CLIENT_PORT' => '9010',
            'XDEBUG_DISCOVER_CLIENT_HOST' => '0',
            'XDEBUG_OUTPUT_DIR' => '/tmp/image-test-xdebug',
        ]);
        $this->startDbgpListeners($container, ['127.0.0.2:9010', '127.0.0.3:9010']);
        $this->assertCommandSucceeds('mkdir -p /tmp/image-test-xdebug && HTTP_X_FORWARDED_FOR=127.0.0.3 php -r \'echo "run";\'', $container);
        $this->waitFor(function () use ($container): void {
            self::assertStringContainsString('<init ', $this->stdout('cat /tmp/image-tests/dbgp/127.0.0.2:9010 2>&1 || true', $container));
        });
        self::assertSame('', $this->stdout('cat /tmp/image-tests/dbgp/127.0.0.3:9010 2>/dev/null || true', $container), 'Without discovery, the address of X-Forwarded-For does not count.');
        self::assertMatchesRegularExpression('/^trace\..+\.xt$/m', $this->stdout('ls /tmp/image-test-xdebug', $container), 'XDEBUG_OUTPUT_DIR');
    }

    /**
     * XDEBUG_TRIGGER_VALUE=image-test, in a container of its own with the default trigger mode and discovery: a script
     * with another trigger value does not connect, one with this value connects to the address of X-Forwarded-For.
     */
    public function testXdebugTriggerValueFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('xdebug-trigger', [
            'XDEBUG_TRIGGER_VALUE' => 'image-test',
            'XDEBUG_CLIENT_PORT' => '9010',
        ]);
        $this->startDbgpListeners($container, ['127.0.0.3:9010']);
        $this->assertCommandSucceeds('XDEBUG_TRIGGER=other HTTP_X_FORWARDED_FOR=127.0.0.3 php -r \'echo "run";\'', $container);
        sleep(1);
        self::assertSame('', $this->stdout('cat /tmp/image-tests/dbgp/127.0.0.3:9010 2>/dev/null || true', $container), 'another trigger value');
        $this->assertCommandSucceeds('XDEBUG_TRIGGER=image-test HTTP_X_FORWARDED_FOR=127.0.0.3 php -r \'echo "run";\'', $container);
        $this->waitFor(function () use ($container): void {
            self::assertStringContainsString('idekey="image-test"', $this->stdout('cat /tmp/image-tests/dbgp/127.0.0.3:9010 2>&1 || true', $container));
        });
    }

    /**
     * Starts Fixtures/opt/image-tests/dbgp-listener.php on each of $addresses, and waits until they listen. Each writes
     * to /tmp/image-tests/dbgp/<address>.
     *
     * @param list<string> $addresses
     */
    private function startDbgpListeners(Container $container, array $addresses): void
    {
        foreach ($addresses as $address) {
            $this->assertCommandSucceeds(sprintf(
                'mkdir -p /tmp/image-tests/dbgp && (nohup php -n /opt/image-tests/dbgp-listener.php %1$s /tmp/image-tests/dbgp/%1$s >> /tmp/image-tests/dbgp/listener.log 2>&1 &)',
                escapeshellarg($address),
            ), $container);
        }

        foreach ($addresses as $address) {
            $this->waitFor(function () use ($container, $address): void {
                [$host, $port] = explode(':', $address);
                self::assertTrue(ListeningSockets::read($container)->isListening((int) $port, $host), $address . ' should listen');
            }, 10, 1);
        }
    }

    /**
     * The XDEBUG_* settings go to 98-webdevops.ini, which every start writes anew, not into /opt/docker/etc/php/php.ini,
     * the file for your own settings. That stays as the image has it, so a mount with :ro works, and its values win.
     */
    public function testXdebugSettingsLeavePhpIniAlone(): void
    {
        $result = $this->runContainer(['sh', '-c', implode('; ', [
            'cat /opt/docker/etc/php/php.ini',
            "php -r 'echo ini_get(\"xdebug.mode\"), PHP_EOL;'",
            "echo 'xdebug.mode = coverage' >> /opt/docker/etc/php/php.ini",
            "php -r 'echo ini_get(\"xdebug.mode\"), PHP_EOL;'",
        ])], ['XDEBUG_MODE' => 'trace']);
        self::assertSame([0, "; placeholder\ntrace\ncoverage\n"], [$result->exitCode, $result->stdout], $result->describe());
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
