<?php

declare(strict_types=1);

namespace Webdevops\Build;

use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function is_dir;
use function is_file;
use function is_link;
use function rtrim;
use function scandir;
use function sort;
use function str_starts_with;

/**
 * The tag directories under docker/: every docker/<image>/<tag>/ with a Dockerfile.twig, the template its
 * Dockerfile is rendered from. Directories that are symbolic links, such as docker/php/8.5, are left out: they show
 * a tag of the image they link to. docker/ is read once, when the object is created.
 */
final readonly class TagDirectories
{
    /**
     * The template next to each Dockerfile.
     */
    public const string TEMPLATE = 'Dockerfile.twig';

    /**
     * @var list<string> every docker/<image>/<tag> directory, sorted like the Dockerfiles in them
     */
    private array $directories;

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath)
    {
        $this->directories = $this->scan();
    }

    /**
     * docker/<image>/<tag> of every tag, or of every tag of $image, relative to the repository root.
     * Sorted like the Dockerfiles in them, so docker/php/8.5-alpine comes before docker/php/8.5.
     *
     * @return list<string>
     */
    public function tags(?string $image = null): array
    {
        return array_values(array_filter(
            $this->directories,
            fn (string $directory): bool => ($image === null || str_starts_with($directory, 'docker/' . $image . '/'))
                && $this->hasTemplate($directory),
        ));
    }

    /**
     * docker/<image>/<tag> of every directory that is not empty but has no template, sorted like tags(): files that
     * are left over, such as a Dockerfile or conf/ of a tag whose template was removed.
     *
     * @return list<string>
     */
    public function withoutTemplate(): array
    {
        return array_values(array_filter(
            $this->directories,
            fn (string $directory): bool => !$this->hasTemplate($directory)
                && array_diff(scandir($this->rootPath . '/' . $directory) ?: [], ['.', '..']) !== [],
        ));
    }

    private function hasTemplate(string $directory): bool
    {
        return is_file($this->rootPath . '/' . $directory . '/' . self::TEMPLATE);
    }

    /**
     * @return list<string>
     */
    private function scan(): array
    {
        $directories = [];
        foreach ($this->subdirectories('docker') as $image) {
            foreach ($this->subdirectories('docker/' . $image) as $tag) {
                // with the slash, sort() orders the directories like the files in them
                $directories[] = 'docker/' . $image . '/' . $tag . '/';
            }
        }

        sort($directories);
        return array_map(fn (string $directory): string => rtrim($directory, '/'), $directories);
    }

    /**
     * The names of the subdirectories of $directory that are no symbolic links. Empty if $directory does not exist
     * or is a symbolic link itself.
     *
     * @return list<string>
     */
    private function subdirectories(string $directory): array
    {
        $path = $this->rootPath . '/' . $directory;
        if (!is_dir($path) || is_link($path)) {
            return [];
        }

        $names = [];
        foreach (scandir($path) ?: [] as $name) {
            if ($name !== '.' && $name !== '..' && is_dir($path . '/' . $name) && !is_link($path . '/' . $name)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
