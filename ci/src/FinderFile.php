<?php

declare(strict_types=1);

namespace Webdevops\Build;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function is_dir;
use function sprintf;
use function unlink;

/**
 * The .DS_Store file that the macOS Finder writes into every folder it shows. It is no source of an image and must
 * not end up in one.
 */
final class FinderFile
{
    public const string NAME = '.DS_Store';

    /**
     * Removes every .DS_Store under $directory. Symbolic links to directories are not followed.
     */
    public static function removeUnder(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->getFilename() === self::NAME && $file->isFile() && !unlink($file->getPathname())) {
                throw new RuntimeException(sprintf('Cannot remove %s.', $file->getPathname()));
            }
        }
    }
}
