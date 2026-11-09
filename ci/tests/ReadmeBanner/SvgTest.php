<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\ReadmeBanner\Svg;

#[CoversClass(Svg::class)]
final class SvgTest extends TestCase
{
    /**
     * @return iterable<string, array{0: float, 1: string}>
     */
    public static function numbers(): iterable
    {
        yield 'integer' => [12.0, '12'];
        yield 'one decimal' => [12.5, '12.5'];
        yield 'rounded' => [3.14159, '3.1'];
        yield 'rounded up to an integer' => [9.96, '10'];
        yield 'negative' => [-7.25001, '-7.3'];
        yield 'negative zero' => [-0.04, '0'];
        yield 'zero' => [0.0, '0'];
    }

    #[DataProvider('numbers')]
    public function testNumberHasAtMostOneDecimal(float $value, string $expected): void
    {
        self::assertSame($expected, Svg::number($value));
    }

    public function testEscapeForXml(): void
    {
        self::assertSame('&lt;a href=&quot;x&quot;&gt;Tom &amp; Jerry&apos;s&lt;/a&gt;', Svg::escape('<a href="x">Tom & Jerry\'s</a>'));
    }
}
