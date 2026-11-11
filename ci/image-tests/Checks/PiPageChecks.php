<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use PHPUnit\Framework\Attributes\DataProvider;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The web server answers with the static page Fixtures/app/pi-number.html, over http and https.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PiPageChecks
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function schemes(): iterable
    {
        yield 'http' => ['http'];
        yield 'https' => ['https'];
    }

    #[DataProvider('schemes')]
    public function testWebServerAnswers(string $scheme): void
    {
        self::assertStringContainsString('3.14159265359', $this->request($scheme . '://localhost/pi-number.html'));
    }
}
