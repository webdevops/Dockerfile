<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\ImageTestCase;

use function array_intersect;
use function str_replace;

/**
 * Mail from PHP through Postfix in the development images, see docs/images/php.md#mail. In the environment container,
 * POSTFIX_RELAYHOST points Postfix to Fixtures/opt/image-tests/smtp-sink.php, a stand-in for a mail catcher.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PostfixChecks
{
    /**
     * POSTFIX_MYNETWORKS=127.0.0.2/32: Postfix relays mail of a client from 127.0.0.2 to other domains, but not of one
     * from 127.0.0.1. POSTFIX_RELAYHOST is checked by the mail that reaches the SMTP sink.
     */
    public function testPostfixNetworksFromTheEnvironment(): void
    {
        $container = $this->environmentContainer();
        $this->eventually(function () use ($container): void {
            self::assertStringStartsWith('250 ', $this->stdout('php -n /opt/image-tests/smtp-relay.php 127.0.0.2 2>&1 || true', $container), 'a client in POSTFIX_MYNETWORKS');
        });
        self::assertStringContainsString('Relay access denied', $this->stdout('php -n /opt/image-tests/smtp-relay.php 127.0.0.1 2>&1 || true', $container), 'a client outside of POSTFIX_MYNETWORKS');
    }

    public function testMailFromThePhpCliReachesTheRelayHost(): void
    {
        $container = $this->mailContainer();

        self::assertSame('sent', $this->stdout(
            "php -r 'echo mail(\"someone@example.test\", \"Image test from \" . PHP_SAPI, \"Sent by the image tests\") ? \"sent\" : \"not sent\";' 2>/dev/null",
            $container,
        ));
        $this->assertMailArrived('cli', $container);
    }

    public function testMailFromPhpFpmReachesTheRelayHost(): void
    {
        if (array_intersect(['nginx', 'apache'], self::services()) === []) {
            self::markTestSkipped('No web server passes requests to PHP-FPM in this image.');
        }

        $container = $this->mailContainer();

        self::assertSame('sent', $this->request('http://localhost/php-test.php?test=mail', container: $container));
        $this->assertMailArrived('fpm-fcgi', $container);
    }

    /**
     * The environment container with the SMTP sink running. A second sink exits, because the port is taken.
     */
    private function mailContainer(): Container
    {
        $container = $this->environmentContainer();
        $this->assertCommandSucceeds('mkdir -p /tmp/image-tests && (nohup php /opt/image-tests/smtp-sink.php >> /tmp/image-tests/smtp-sink.log 2>&1 &)', $container);
        $this->waitFor(function () use ($container): void {
            $this->assertCommandSucceeds("php -r 'exit(@fsockopen(\"127.0.0.1\", 2525) ? 0 : 1);'", $container);
        });
        return $container;
    }

    /**
     * A mail that PHP with $sapi sent arrived at the SMTP sink, with the sender of PHP_SENDMAIL_PATH.
     */
    private function assertMailArrived(string $sapi, Container $container): void
    {
        $this->waitFor(function () use ($sapi, $container): void {
            $mail = $this->stdout('cat $(grep -l "^Subject: Image test from ' . $sapi . '" /tmp/image-tests/mail/*.eml 2>/dev/null) < /dev/null', $container);
            self::assertStringContainsString('X-Image-Test-Mail-From: <image-test@example.test>', $mail, 'the sender of PHP_SENDMAIL_PATH');
            self::assertStringContainsString("To: someone@example.test\n", str_replace("\r\n", "\n", $mail));
        });
    }
}
