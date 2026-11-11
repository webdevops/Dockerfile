<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * docker-service installs, enables and starts the services that webdevops/base does not run by default.
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
        $this->assertCommandSucceeds('docker-service install ' . $service);
        $this->assertCommandSucceeds('docker-service enable ' . $service);
        $this->assertCommandSucceeds('docker-service start ' . $service);
        $this->eventually(function () use ($service): void {
            $result = $this->execute('docker-service check ' . $service);
            self::assertStringContainsString('ok', $result->stdout, $result->describe());
            self::assertSame(0, $result->exitCode, $result->describe());
        }, 5);
    }
}
