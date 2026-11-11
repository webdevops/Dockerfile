<?php

declare(strict_types=1);

namespace Webdevops\Build\Dockerfile;

use RuntimeException;
use Webdevops\Build\TagDirectories;

use function array_map;
use function basename;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_dir;
use function is_file;
use function is_link;
use function mkdir;
use function readlink;
use function scandir;
use function sprintf;
use function symlink;
use function unlink;

/**
 * Keeps the tag directories of an image that is built from the Dockerfiles of another image as symbolic links:
 * docker/php/<tag> links to docker/php-official/<tag> for every tag of php-official, and no other link stays there.
 */
final readonly class ImageLinks
{
    /**
     * The images whose tags are links, each with the image they link to.
     */
    public const array LINKS = ['php' => 'php-official'];

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath)
    {
    }

    public function update(TagDirectories $tagDirectories): void
    {
        foreach (self::LINKS as $image => $target) {
            $directory = $this->rootPath . '/docker/' . $image;
            if (!is_dir($directory) && !mkdir($directory) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Cannot create docker/%s.', $image));
            }

            $tags = array_map(basename(...), $tagDirectories->tags($target));
            foreach (scandir($directory) ?: [] as $entry) {
                if (is_link($directory . '/' . $entry) && !in_array($entry, $tags, true) && !unlink($directory . '/' . $entry)) {
                    throw new RuntimeException(sprintf('Cannot remove docker/%s/%s.', $image, $entry));
                }
            }

            foreach ($tags as $tag) {
                $this->link($image . '/' . $tag, '../' . $target . '/' . $tag);
            }
        }
    }

    /**
     * Makes docker/$path a symbolic link to $target.
     */
    private function link(string $path, string $target): void
    {
        $link = $this->rootPath . '/docker/' . $path;
        if (is_link($link)) {
            if (readlink($link) === $target) {
                return;
            }

            if (!unlink($link)) {
                throw new RuntimeException(sprintf('Cannot remove docker/%s.', $path));
            }
        } elseif (is_file($link) && file_get_contents($link) === $target) {
            // Git without symbolic links (core.symlinks=false) checks a link out as a file that holds its target
            return;
        } elseif (file_exists($link)) {
            throw new RuntimeException(sprintf('docker/%s has to be a symbolic link to %s, but is a file or a directory.', $path, $target));
        }

        if (!symlink($target, $link)) {
            throw new RuntimeException(sprintf('Cannot link docker/%s to %s.', $path, $target));
        }
    }
}
