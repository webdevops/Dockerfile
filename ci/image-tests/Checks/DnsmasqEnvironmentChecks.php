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
            'SERVICE_DNSMASQ_OPTS' => '--address=/options.image-test.example/127.0.0.2',
            'SERVICE_DNSMASQ_USER' => 'nobody',
        ]);
        $this->assertCommandSucceeds('docker-service enable dnsmasq && docker-service start dnsmasq', $container);

        // SERVICE_DNSMASQ_USER=nobody: the user of the process
        $this->eventually(function () use ($container): void {
            self::assertSame(trim($this->stdout('id -u nobody', $container)), trim($this->stdout(
                'for process in /proc/[0-9]*; do if tr "\\000" " " < $process/cmdline 2>/dev/null | grep -q "^dnsmasq "; then awk \'/^Uid:/ { print $2 }\' $process/status; fi; done',
                $container,
            )));
        });
        self::assertSame("root\n", $this->stdout('cat /tmp/image-tests/hooks/dnsmasq.d', $container), 'the start script sources the hooks in dnsmasq.d');
        // the start script makes dnsmasq the name server of the container
        // VIRTUAL_HOST, and SERVICE_DNSMASQ_OPTS with an address of its own
        $hosts = ['app.image-test.example' => '127.0.0.1', 'any.wildcard.image-test.example' => '127.0.0.1', 'options.image-test.example' => '127.0.0.2'];
        foreach ($hosts as $host => $address) {
            $this->eventually(function () use ($container, $host, $address): void {
                // musl repeats the name as an alias
                self::assertMatchesRegularExpression('/^' . preg_quote($address, '/') . '\s+' . preg_quote($host, '/') . '(\s|$)/', trim($this->stdout('getent hosts ' . $host . ' || true', $container)));
            });
        }
    }
}
