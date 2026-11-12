<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * docker-service installs, enables and starts the services that webdevops/base does not run by default. It does so in a
 * container of its own, so that the container of the class keeps the services of the image.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait AdditionalServicesChecks
{
    /**
     * @return array<string, array{string}>
     */
    public static function additionalServices(): array
    {
        return self::cases([
            'cron',
            'dnsmasq',
            'postfix',
            'ssh',
            'syslog',
        ]);
    }

    #[DataProvider('additionalServices')]
    public function testAdditionalServiceStarts(string $service): void
    {
        $container = $this->startContainer('additional-services');
        $this->assertCommandSucceeds('docker-service install ' . $service, $container);
        $this->assertCommandSucceeds('docker-service enable ' . $service, $container);
        $this->assertCommandSucceeds('docker-service start ' . $service, $container);
        // the check itself just started the service, so it waits with all attempts, also after a slow service
        $this->waitFor(function () use ($service, $container): void {
            $result = $this->execute('docker-service check ' . $service, $container);
            self::assertStringContainsString('ok', $result->stdout, $result->describe());
            self::assertSame(0, $result->exitCode, $result->describe());
        }, 5);
    }
}
