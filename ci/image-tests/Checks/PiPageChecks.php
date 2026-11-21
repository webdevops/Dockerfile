<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\Docker\ImageConfiguration;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The web server answers with the static page Fixtures/app/pi-number.html, over http and https.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PiPageChecks
{
    #[DataProvider('webSchemes')]
    public function testWebServerAnswers(string $scheme): void
    {
        self::assertStringContainsString('3.14159265359', $this->request($scheme . '://localhost/pi-number.html'));
    }

    public function testImageExposesHttpAndHttps(): void
    {
        $exposedPorts = ImageConfiguration::inspect(self::image()->reference)->exposedPorts;
        self::assertContains('80/tcp', $exposedPorts);
        self::assertContains('443/tcp', $exposedPorts);
    }
}
