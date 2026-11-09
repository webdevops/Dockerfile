<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function end;
use function file_get_contents;
use function preg_match;
use function sort;
use function str_ends_with;
use function substr;
use function usort;
use function version_compare;

/**
 * What the README banner shows about the PHP images, taken from the images
 * the CI builds: web server flavors, PHP versions, variants, the Debian
 * release and the platforms.
 */
final class ImageMatrix
{
    /** @var array<string, true> */
    private array $flavors = [];
    /** @var array<string, true> */
    private array $versions = [];
    private bool $hasDev = false;
    private bool $hasAlpine = false;
    /** @var list<string> */
    private array $debianReleases = [];

    /**
     * @param list<array{image: string, tag: string, file: string}> $images published images, e.g. php-nginx-dev with tag 8.5-alpine
     * @param list<string> $platforms e.g. linux/amd64
     */
    public function __construct(array $images, private readonly array $platforms)
    {
        foreach ($images as $image) {
            if (!preg_match('/^php(-[a-z0-9]+)*$/', $image['image'])
                || !preg_match('/^(?P<version>\d+\.\d+)(?P<alpine>-alpine)?$/', $image['tag'], $tag)) {
                continue;
            }
            $flavor = $image['image'];
            if (str_ends_with($flavor, '-dev')) {
                $flavor = substr($flavor, 0, -4);
                $this->hasDev = true;
            }
            $this->flavors[$flavor] = true;
            $this->versions[$tag['version']] = true;
            if (!empty($tag['alpine'])) {
                $this->hasAlpine = true;
            } elseif ($image['image'] === 'php') {
                // The other PHP images build on webdevops/php, which names the Debian release
                $this->debianReleases[] = $this->releaseFromDockerfile($image['file']);
            }
        }
        if (!isset($this->flavors['php'])) {
            throw new RuntimeException('The README banner found no webdevops/php images to show.');
        }
        $this->debianReleases = array_values(array_unique($this->debianReleases));
    }

    /**
     * @return list<string> php first, then the web server flavors in alphabetical order
     */
    public function flavors(): array
    {
        $flavors = array_keys($this->flavors);
        usort($flavors, fn (string $a, string $b): int => [$a !== 'php', $a] <=> [$b !== 'php', $b]);
        return $flavors;
    }

    /**
     * @return list<string> oldest first
     */
    public function versions(): array
    {
        $versions = array_keys($this->versions);
        usort($versions, fn (string $a, string $b): int => version_compare($a, $b));
        return $versions;
    }

    public function newestVersion(): string
    {
        $versions = $this->versions();
        return end($versions);
    }

    public function hasDevVariant(): bool
    {
        return $this->hasDev;
    }

    public function hasAlpine(): bool
    {
        return $this->hasAlpine;
    }

    /**
     * @return string|null the Debian release name, or null if the versions use different releases
     */
    public function debianRelease(): ?string
    {
        return count($this->debianReleases) === 1 ? $this->debianReleases[0] : null;
    }

    /**
     * @return list<string>
     */
    public function platforms(): array
    {
        $platforms = $this->platforms;
        sort($platforms);
        return $platforms;
    }

    private function releaseFromDockerfile(string $dockerfile): string
    {
        // e.g. "FROM php:8.5-fpm-bookworm"
        if (!preg_match('/^FROM\s+php:\S+-(?P<release>[a-z]+)\s*$/mi', file_get_contents($dockerfile), $match)) {
            throw new RuntimeException('The README banner found no Debian release in the FROM line of ' . $dockerfile);
        }
        return $match['release'];
    }
}
