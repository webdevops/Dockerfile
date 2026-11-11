<?php

declare(strict_types=1);

namespace Webdevops\Build\Provision;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\FinderFile;
use Webdevops\Build\TagDirectories;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function chmod;
use function copy;
use function explode;
use function fileperms;
use function implode;
use function is_array;
use function is_dir;
use function is_link;
use function is_string;
use function mkdir;
use function preg_match;
use function preg_quote;
use function rmdir;
use function scandir;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function unlink;

/**
 * Copies the files of provisioning/ into the conf/ directory next to each Dockerfile, as conf/provision.yml says.
 *
 * For each image in conf/provision.yml, the conf/ directories of all its tags are emptied first. Then each source
 * directory is copied into the conf/ directory of the tags it names, in the order of the file, so a later source
 * replaces the files of an earlier one. Images that conf/provision.yml does not name are left alone.
 * conf/provision.yml is checked completely before any file is changed.
 *
 * @phpstan-type Plan array<string, array{directories: list<string>, sources: array<string, array{tags: list<string>, directories: list<string>}>}>
 */
final readonly class Provisioner
{
    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath = __DIR__ . '/../../..')
    {
    }

    /**
     * Provisions every image of conf/provision.yml.
     *
     * @param ?callable(string): void $log receives a line for each source that is copied
     */
    public function provision(?callable $log = null): void
    {
        foreach ($this->plan() as $image => $plan) {
            foreach ($plan['directories'] as $directory) {
                $this->remove($this->rootPath . '/' . $directory . '/conf');
            }

            foreach ($plan['sources'] as $source => $copy) {
                foreach ($copy['directories'] as $directory) {
                    $this->copyTree($this->rootPath . '/provisioning/' . $source, $this->rootPath . '/' . $directory . '/conf');
                }

                if ($log !== null) {
                    $log(sprintf('%s => webdevops/%s:%s', $source, $image, implode(', ', $copy['tags'])));
                }
            }
        }
    }

    /**
     * What conf/provision.yml copies, in its order: for each image the tag directories whose conf/ is emptied, and for
     * each source directory its tag patterns and the tag directories they match.
     *
     * @return Plan
     */
    private function plan(): array
    {
        $images = Yaml::parseFile($this->rootPath . '/conf/provision.yml')['provision'] ?? null;
        if (!is_array($images)) {
            throw new RuntimeException('conf/provision.yml has no "provision" section.');
        }

        $tagDirectories = new TagDirectories($this->rootPath);
        $plan = [];
        foreach ($images as $image => $settings) {
            $image = (string)$image;
            // only "configuration", so that a misspelt key does not just empty the conf/ directories
            if (!is_array($settings) || array_keys($settings) !== ['configuration'] || !is_array($settings['configuration'])) {
                throw new RuntimeException(sprintf('conf/provision.yml: webdevops/%s needs "configuration", which maps directories of provisioning/ to tags, and no other setting.', $image));
            }

            $sources = $settings['configuration'];

            $directories = $tagDirectories->tags($image);
            $plan[$image] = ['directories' => $directories, 'sources' => []];
            foreach ($sources as $source => $tags) {
                $source = (string)$source;
                if (!is_dir($this->rootPath . '/provisioning/' . $source)) {
                    throw new RuntimeException(sprintf('conf/provision.yml copies provisioning/%s into webdevops/%s, but it does not exist.', $source, $image));
                }

                $tags = is_array($tags) ? array_values($tags) : [$tags];
                if ($tags === []) {
                    throw new RuntimeException(sprintf('conf/provision.yml has no tag for provisioning/%s in webdevops/%s.', $source, $image));
                }

                $matches = [];
                foreach ($tags as $tag) {
                    if (!is_string($tag) || $tag === '') {
                        throw new RuntimeException(sprintf('conf/provision.yml has an invalid tag for provisioning/%s in webdevops/%s.', $source, $image));
                    }

                    $tagMatches = $this->matching($directories, $tag);
                    if ($tagMatches === []) {
                        throw new RuntimeException(sprintf('conf/provision.yml copies provisioning/%s into webdevops/%s:%s, but webdevops/%s has no such tag under docker/.', $source, $image, $tag, $image));
                    }

                    $matches = array_merge($matches, $tagMatches);
                }

                $plan[$image]['sources'][$source] = ['tags' => $tags, 'directories' => array_values(array_unique($matches))];
            }
        }

        return $plan;
    }

    /**
     * The directories whose tag matches $tag, regardless of case. * in $tag stands for one or more characters.
     *
     * @param list<string> $directories docker/<image>/<tag>
     * @return list<string>
     */
    private function matching(array $directories, string $tag): array
    {
        $pattern = '#^' . implode('[^/]+', array_map(fn (string $part): string => preg_quote($part, '#'), explode('*', $tag))) . '$#i';
        return array_values(array_filter($directories, fn (string $directory): bool => preg_match($pattern, basename($directory)) === 1));
    }

    /**
     * Copies the content of $source into $target, following symbolic links and keeping the permissions of the files.
     * The .DS_Store files of the macOS Finder are left out, so that they do not end up in an image.
     */
    private function copyTree(string $source, string $target): void
    {
        if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            throw new RuntimeException(sprintf('Cannot create %s.', $this->relative($target)));
        }

        foreach ($this->entries($source) as $entry) {
            if ($entry === FinderFile::NAME) {
                continue;
            }

            $from = $source . '/' . $entry;
            $to = $target . '/' . $entry;
            if (is_dir($from)) {
                $this->copyTree($from, $to);
                continue;
            }

            if (!copy($from, $to) || !chmod($to, fileperms($from) & 0777)) {
                throw new RuntimeException(sprintf('Cannot copy %s to %s.', $this->relative($from), $this->relative($to)));
            }
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path) || is_link($path)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $removed = $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                if (!$removed) {
                    throw new RuntimeException(sprintf('Cannot remove %s.', $this->relative($file->getPathname())));
                }
            }
        }

        if (!rmdir($path)) {
            throw new RuntimeException(sprintf('Cannot remove %s.', $this->relative($path)));
        }
    }

    /**
     * The names in a directory without . and .., sorted.
     *
     * @return list<string>
     */
    private function entries(string $directory): array
    {
        $entries = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $entries[] = $entry;
            }
        }

        sort($entries);
        return $entries;
    }

    /**
     * $path relative to the repository root, for messages.
     */
    private function relative(string $path): string
    {
        $root = $this->rootPath . '/';
        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
