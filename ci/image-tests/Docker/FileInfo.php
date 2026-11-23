<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use InvalidArgumentException;

use function array_keys;
use function escapeshellarg;
use function explode;
use function octdec;
use function sprintf;
use function str_contains;
use function strtoupper;

/**
 * Type, owner and mode of a path in the container, read with one command. The type checks follow symbolic links
 * (test -f, test -d), owner and mode do not (stat).
 */
final readonly class FileInfo
{
    private const array ACCESS_BITS = ['r' => 4, 'w' => 2, 'x' => 1];

    private const array CLASS_SHIFT = ['owner' => 6, 'group' => 3, 'others' => 0];

    /**
     * Position in permissions(), bit and letter of setuid, setgid and the sticky bit
     */
    private const array SPECIAL_BITS = [3 => [04000, 's'], 6 => [02000, 's'], 9 => [01000, 't']];

    public function __construct(
        public string $path,
        public bool $exists,
        public bool $isFile,
        public bool $isDirectory,
        public bool $isSymlink,
        public bool $isCharacterDevice,
        public bool $isExecutable,
        public string $owner,
        public string $group,
        public int $mode,
    ) {
    }

    public static function read(Container $container, string $path): self
    {
        $result = $container->exec(sprintf(
            'f=%s; s=$(stat -c "%%U|%%G|%%a" "$f" 2>/dev/null); t=; for o in e f d L c x; do if test -$o "$f"; then t="$t$o"; fi; done; printf "%%s\n%%s\n" "$s" "$t"',
            escapeshellarg($path),
        ));

        return self::parse($path, $result->stdout);
    }

    /**
     * @param string $output two lines: "owner|group|octal mode" (empty if the path does not exist) and the letters of
     *                       the test options that apply, e.g. "efx"
     */
    public static function parse(string $path, string $output): self
    {
        [$stat, $tests] = explode("\n", $output . "\n\n", 3);
        [$owner, $group, $mode] = explode('|', $stat . '||', 4);

        return new self(
            $path,
            str_contains($tests, 'e'),
            str_contains($tests, 'f'),
            str_contains($tests, 'd'),
            str_contains($tests, 'L'),
            str_contains($tests, 'c'),
            str_contains($tests, 'x'),
            $owner,
            $group,
            $mode === '' ? 0 : (int)octdec($mode),
        );
    }

    /**
     * Whether the mode lets $class (owner, group or others) $access (r, w or x) the path.
     */
    public function permits(string $class, string $access): bool
    {
        if (!isset(self::CLASS_SHIFT[$class], self::ACCESS_BITS[$access])) {
            throw new InvalidArgumentException(sprintf('Unknown class "%s" or access "%s".', $class, $access));
        }

        return ($this->mode >> self::CLASS_SHIFT[$class] & self::ACCESS_BITS[$access]) !== 0;
    }

    /**
     * Type and mode as "ls -l" and "stat -c %A" show them, e.g. -rwxr-xr-x for a program, drwxr-xr-x for a directory
     * and -rwsr-sr-x for a program with the setuid and setgid bits; empty if the path does not exist.
     */
    public function permissions(): string
    {
        if (!$this->exists && !$this->isSymlink) {
            return '';
        }

        $permissions = match (true) {
            $this->isSymlink => 'l',
            $this->isDirectory => 'd',
            $this->isCharacterDevice => 'c',
            $this->isFile => '-',
            default => '?',
        };
        foreach (array_keys(self::CLASS_SHIFT) as $class) {
            foreach (array_keys(self::ACCESS_BITS) as $access) {
                $permissions .= $this->permits($class, $access) ? $access : '-';
            }
        }

        // setuid, setgid and sticky bit take the place of the x of the owner, the group and others, in upper case if
        // that x is not set
        foreach (self::SPECIAL_BITS as $position => [$bit, $letter]) {
            if (($this->mode & $bit) !== 0) {
                $permissions[$position] = ($this->mode & (1 << (9 - $position))) !== 0 ? $letter : strtoupper($letter);
            }
        }

        return $permissions;
    }
}
