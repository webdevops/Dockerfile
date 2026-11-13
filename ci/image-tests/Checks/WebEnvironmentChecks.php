<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function escapeshellarg;

/**
 * The web server variables WEB_DOCUMENT_ROOT, WEB_DOCUMENT_INDEX, WEB_ALIAS_DOMAIN and WEB_PHP_SOCKET, see
 * docs/environment-variables.md#web-server. Fixtures/ has the document root /app/image-test-root.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait WebEnvironmentChecks
{
    public function testDocumentRootAndIndexFromTheEnvironment(): void
    {
        self::assertSame(
            "Document root and index from WEB_DOCUMENT_ROOT and WEB_DOCUMENT_INDEX\n",
            $this->request('http://localhost/', container: $this->environmentContainer()),
        );
    }

    /**
     * WEB_ALIAS_DOMAIN=image-test.example, in a container of its own with a second virtual host from Hooks/ that answers
     * 410 to all host names the default virtual host does not have: a request for the alias reaches the default one.
     */
    public function testAliasDomainFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('alias', ['WEB_ALIAS_DOMAIN' => 'image-test.example', 'IMAGE_TEST_OTHER_VHOST' => '1']);
        // 127.0.0.1: the second virtual host listens only on IPv4, localhost may be ::1
        $status = fn (string $host): string => $this->statusCode('http://127.0.0.1/pi-number.html', '--header ' . escapeshellarg('Host: ' . $host), $container);
        $this->eventually(static function () use ($status): void {
            self::assertSame('410', $status('other.example'), 'a host name of the second virtual host');
        });
        self::assertSame('200', $status('image-test.example'), 'the alias of the default virtual host');
    }

    /**
     * WEB_PHP_SOCKET=127.0.0.1:9999, in a container of its own: the web server passes PHP requests to that address,
     * where nothing listens, and logs it.
     */
    public function testPhpSocketFromTheEnvironment(): void
    {
        $container = $this->startTestContainer('php-socket', ['WEB_PHP_SOCKET' => '127.0.0.1:9999']);
        $this->eventually(function () use ($container): void {
            // Nginx answers 502, Apache 503
            self::assertContains($this->statusCode('http://localhost/php-test.php?test=sha1', container: $container), ['502', '503']);
            self::assertStringContainsString('127.0.0.1:9999', $container->logs());
        });
    }
}
