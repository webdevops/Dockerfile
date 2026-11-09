<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ReadmeBanner\Glyphs;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ReadmeBanner\Glyphs\BinaryData;

#[CoversClass(BinaryData::class)]
final class BinaryDataTest extends TestCase
{
    public function testReadsBigEndianNumbers(): void
    {
        $data = new BinaryData("\x01\x02\xFF\xFE\x80\x00\x7F\xFF");

        self::assertSame(1, $data->uint8(0));
        self::assertSame(255, $data->uint8(2));
        self::assertSame(-1, $data->int8(2));
        self::assertSame(127, $data->int8(6));
        self::assertSame(0x0102, $data->uint16(0));
        self::assertSame(0xFFFE, $data->uint16(2));
        self::assertSame(-2, $data->int16(2));
        self::assertSame(-32768, $data->int16(4));
        self::assertSame(32767, $data->int16(6));
        self::assertSame(0x0102FFFE, $data->uint32(0));
        self::assertSame(0x80007FFF, $data->uint32(4));
    }

    /**
     * @return iterable<string, array{0: string, 1: float}>
     */
    public static function f2dot14(): iterable
    {
        yield 'one' => ["\x40\x00", 1.0];
        yield 'half' => ["\x20\x00", 0.5];
        yield 'minus two' => ["\x80\x00", -2.0];
        yield 'almost two' => ["\x7F\xFF", 32767 / 16384];
    }

    #[DataProvider('f2dot14')]
    public function testReadsFixedPointNumbers(string $bytes, float $expected): void
    {
        self::assertSame($expected, new BinaryData($bytes)->f2dot14(0));
    }

    public function testReadsTagsAndSlices(): void
    {
        $data = new BinaryData('headglyfloca');

        self::assertSame('glyf', $data->tag(4));
        self::assertSame('loca', $data->slice(4, 8)->tag(4));
    }

    /**
     * @return iterable<string, array{0: callable(BinaryData): mixed, 1: int}>
     */
    public static function readsPastTheEnd(): iterable
    {
        yield 'uint8' => [fn (BinaryData $data): int => $data->uint8(4), 5];
        yield 'uint16' => [fn (BinaryData $data): int => $data->uint16(3), 5];
        yield 'uint32' => [fn (BinaryData $data): int => $data->uint32(2), 6];
        yield 'tag' => [fn (BinaryData $data): string => $data->tag(1), 5];
        yield 'slice' => [fn (BinaryData $data): BinaryData => $data->slice(2, 3), 5];
        yield 'negative offset' => [fn (BinaryData $data): int => $data->uint8(-1), 0];
    }

    /**
     * @param callable(BinaryData): mixed $read
     */
    #[DataProvider('readsPastTheEnd')]
    public function testReadingPastTheEndIsAnError(callable $read, int $end): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The font file is damaged: it ends before byte ' . $end . '.');
        $read(new BinaryData("\0\0\0\0"));
    }
}
