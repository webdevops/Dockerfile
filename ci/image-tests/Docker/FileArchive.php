<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use FilesystemIterator;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function is_file;
use function random_bytes;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function unlink;

/**
 * A tar archive of the files under a directory, at their paths relative to it, for "docker cp - <container>:/". The
 * files get mode 644, or 755 if they are executable, owned by root.
 *
 * The archive holds no directory entries. docker cp gives a directory that already exists in the container the owner
 * and mode of its entry, so /app would belong to root. Without them, existing directories keep their owner and mode,
 * and missing ones are created for root with mode 755.
 */
final readonly class FileArchive
{
    private function __construct(public string $path)
    {
    }

    public static function create(string $directory): self
    {
        $path = sys_get_temp_dir() . '/image-test-files-' . bin2hex(random_bytes(8)) . '.tar';
        $archive = new PharData($path);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile()) {
                $name = substr($file->getPathname(), strlen($directory) + 1);
                $archive->addFile($file->getPathname(), $name);
                // readable by everybody, whatever the umask of the checkout: the web server and PHP-FPM read the files
                $archive[$name]->chmod($file->isExecutable() ? 0755 : 0644);
            }
        }

        return new self($path);
    }

    /**
     * Whether the archive has any file. PharData writes nothing for a directory without files.
     */
    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function remove(): void
    {
        if ($this->exists()) {
            unlink($this->path);
        }
    }
}
