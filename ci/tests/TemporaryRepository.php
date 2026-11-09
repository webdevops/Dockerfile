<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function copy;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function unlink;

/**
 * A copy of tests/Fixtures/repository in the temporary directory, for code that writes files.
 */
final readonly class TemporaryRepository
{
    public const string FIXTURE = __DIR__ . '/Fixtures/repository';

    public const string GLYPHS = __DIR__ . '/../resources/readme-banner/glyphs.json';

    public string $path;

    public function __construct()
    {
        $this->path = sys_get_temp_dir() . '/webdevops-ci-test-' . bin2hex(random_bytes(6));
        mkdir($this->path);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::FIXTURE, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            $target = $this->path . substr($file->getPathname(), strlen(self::FIXTURE));
            if ($file->isDir()) {
                mkdir($target);
            } else {
                copy($file->getPathname(), $target);
            }
        }

        // The real glyph table, which the README banner draws with
        $this->write('ci/resources/readme-banner/glyphs.json', (string)file_get_contents(self::GLYPHS));
    }

    public function read(string $file): string
    {
        return (string)file_get_contents($this->path . '/' . $file);
    }

    public function write(string $file, string $content): void
    {
        $path = $this->path . '/' . $file;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);
    }

    public function remove(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        rmdir($this->path);
    }
}
