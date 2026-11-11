<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

use function preg_quote;
use function trim;

/**
 * VIRTUAL_HOST, SERVICE_DNSMASQ_OPTS, SERVICE_DNSMASQ_USER and the hooks of dnsmasq in a container that enables
 * dnsmasq, see docs/environment-variables.md#services and docs/customization.md#service-hooks.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait DnsmasqEnvironmentChecks
{
    public function testDnsmasqWithVirtualHostsFromTheEnvironment(): void
    {
        $container = $this->startContainer('dnsmasq', [
            'VIRTUAL_HOST' => 'app.image-test.example,*.wildcard.image-test.example',
            'SERVICE_DNSMASQ_OPTS' => '--log-queries',
            'SERVICE_DNSMASQ_USER' => 'nobody',
        ]);
        $this->assertCommandSucceeds('docker-service enable dnsmasq && docker-service start dnsmasq', $container);

        $this->eventually(function () use ($container): void {
            self::assertContains('dnsmasq --keep-in-foreground --user=nobody --log-queries', $this->commandLines($container));
        });
        self::assertSame("root\n", $this->stdout('cat /tmp/image-tests/hooks/dnsmasq.d', $container), 'the start script sources the hooks in dnsmasq.d');
        // the start script makes dnsmasq the name server of the container
        foreach (['app.image-test.example', 'any.wildcard.image-test.example'] as $host) {
            $this->eventually(function () use ($container, $host): void {
                // musl repeats the name as an alias
                self::assertMatchesRegularExpression('/^127\.0\.0\.1\s+' . preg_quote($host, '/') . '(\s|$)/', trim($this->stdout('getent hosts ' . $host . ' || true', $container)));
            });
        }
    }
}
