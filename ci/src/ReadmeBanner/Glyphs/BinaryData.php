<?php

namespace Webdevops\Build\ReadmeBanner\Glyphs;

use RuntimeException;

use function ord;
use function sprintf;
use function strlen;
use function substr;

/**
 * Big-endian reader for the binary tables of a font file.
 */
final readonly class BinaryData
{
    public function __construct(private string $bytes)
    {
    }

    public function slice(int $offset, int $length): self
    {
        $this->assertReadable($offset, $length);
        return new self(substr($this->bytes, $offset, $length));
    }

    public function tag(int $offset): string
    {
        $this->assertReadable($offset, 4);
        return substr($this->bytes, $offset, 4);
    }

    public function uint8(int $offset): int
    {
        $this->assertReadable($offset, 1);
        return ord($this->bytes[$offset]);
    }

    public function int8(int $offset): int
    {
        $value = $this->uint8($offset);
        return $value >= 0x80 ? $value - 0x100 : $value;
    }

    public function uint16(int $offset): int
    {
        $this->assertReadable($offset, 2);
        return ord($this->bytes[$offset]) << 8 | ord($this->bytes[$offset + 1]);
    }

    public function int16(int $offset): int
    {
        $value = $this->uint16($offset);
        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    public function uint32(int $offset): int
    {
        return $this->uint16($offset) << 16 | $this->uint16($offset + 2);
    }

    /**
     * Fixed-point number with 14 fractional bits, as used for component scales.
     */
    public function f2dot14(int $offset): float
    {
        return $this->int16($offset) / 16384;
    }

    private function assertReadable(int $offset, int $length): void
    {
        if ($offset < 0 || $offset + $length > strlen($this->bytes)) {
            throw new RuntimeException(sprintf(
                'The font file is damaged: it ends before byte %d.',
                $offset + $length,
            ));
        }
    }
}
