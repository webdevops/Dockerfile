<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use function bin2hex;
use function hex2bin;
use function hexdec;
use function implode;
use function inet_ntop;
use function inet_pton;
use function preg_match;
use function preg_split;
use function str_contains;
use function str_split;
use function strlen;
use function strrev;
use function strtolower;

/**
 * The TCP sockets that listen in the container, from /proc/net/tcp and /proc/net/tcp6. Reading the kernel's tables
 * needs neither netstat nor ss in the image.
 */
final readonly class ListeningSockets
{
    /**
     * State of a listening socket in /proc/net/tcp
     */
    private const string LISTEN = '0A';

    /**
     * @param list<array{address: string, port: int}> $sockets
     */
    public function __construct(public array $sockets)
    {
    }

    public static function read(Container $container): self
    {
        return self::parse($container->exec('cat /proc/net/tcp /proc/net/tcp6 2>/dev/null')->stdout);
    }

    /**
     * @param string $tables content of /proc/net/tcp and /proc/net/tcp6, header lines included
     */
    public static function parse(string $tables): self
    {
        $sockets = [];
        foreach (preg_split('/\R/', $tables) ?: [] as $line) {
            // "  0: 00000000:0050 00000000:0000 0A ...": local address and port in hex, remote address, state
            if (!preg_match('/^\s*\d+:\s+([0-9A-Fa-f]+):([0-9A-Fa-f]{4})\s+\S+\s+([0-9A-Fa-f]{2})\s/', $line, $matches)) {
                continue;
            }

            if (strtolower($matches[3]) !== strtolower(self::LISTEN)) {
                continue;
            }

            $address = self::address($matches[1]);
            if ($address !== null) {
                $sockets[] = ['address' => $address, 'port' => (int)hexdec($matches[2])];
            }
        }

        return new self($sockets);
    }

    /**
     * Whether a socket listens on $port, on $address if given (e.g. 0.0.0.0, ::, 127.0.0.1, ::1).
     */
    public function isListening(int $port, ?string $address = null): bool
    {
        $wanted = $address === null ? null : inet_pton($address);
        return array_any($this->sockets, fn(array $socket): bool => $socket['port'] === $port && ($wanted === null || inet_pton($socket['address']) === $wanted));
    }

    /**
     * Readable list of the sockets for failure messages, e.g. "0.0.0.0:80, [::]:80".
     */
    public function describe(): string
    {
        $list = [];
        foreach ($this->sockets as $socket) {
            $list[] = (str_contains($socket['address'], ':') ? '[' . $socket['address'] . ']' : $socket['address']) . ':' . $socket['port'];
        }

        return $list === [] ? 'none' : implode(', ', $list);
    }

    /**
     * The kernel writes each 32-bit word of the address in host byte order, which is little-endian on amd64 and arm64.
     */
    private static function address(string $hex): ?string
    {
        if (strlen($hex) !== 8 && strlen($hex) !== 32) {
            return null;
        }

        $binary = '';
        foreach (str_split($hex, 8) as $word) {
            $bytes = hex2bin($word);
            if ($bytes === false) {
                return null;
            }

            $binary .= strrev($bytes);
        }

        $address = inet_ntop($binary);
        return $address === false ? bin2hex($binary) : $address;
    }
}
