<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_keys;
use function array_unique;
use function array_values;
use function end;
use function file_get_contents;
use function preg_match;
use function sort;
use function str_ends_with;
use function substr;
use function uksort;
use function usort;
use function version_compare;

/**
 * What the README banner shows about the PHP images, taken from the images
 * the CI builds: web server flavors, PHP versions, variants, base OS
 * suffixes with their releases, and the platforms.
 */
final class ImageMatrix
{
    /** Base OS of tags without a suffix, e.g. 8.5 */
    public const NO_SUFFIX = '';

    /** @var array<string, true> */
    private array $flavors = [];
    /** @var array<string, true> */
    private array $versions = [];
    private bool $hasDev = false;
    /** @var array<string, array<string, true>> tags per image, e.g. php-nginx-dev => 8.5-alpine */
    private array $tags = [];
    /** @var array<string, true> */
    private array $suffixes = [];
    /** @var array<string, array<string, string>> Dockerfile of webdevops/php per base OS suffix and PHP version */
    private array $phpDockerfiles = [];

    /**
     * @param list<array{image: string, tag: string, file: string}> $images published images, e.g. php-nginx-dev with tag 8.5-alpine
     * @param list<string> $platforms e.g. linux/amd64
     */
    public function __construct(array $images, private readonly array $platforms)
    {
        foreach ($images as $image) {
            if (!preg_match('/^php(-[a-z0-9]+)*$/', $image['image'])
                || !preg_match('/^(?P<version>\d+\.\d+)(?:-(?P<suffix>[a-z][a-z0-9]*))?$/', $image['tag'], $tag)) {
                continue;
            }
            $flavor = $image['image'];
            if (str_ends_with($flavor, '-dev')) {
                $flavor = substr($flavor, 0, -4);
                $this->hasDev = true;
            }
            $this->flavors[$flavor] = true;
            $this->versions[$tag['version']] = true;
            $this->tags[$image['image']][$image['tag']] = true;
            $suffix = $tag['suffix'] ?? self::NO_SUFFIX;
            $this->suffixes[$suffix] = true;
            if ($image['image'] === 'php') {
                // The other PHP images build on webdevops/php, which names the base OS release
                $this->phpDockerfiles[$suffix][$tag['version']] = $image['file'];
            }
        }
        if (!isset($this->flavors['php'])) {
            throw new RuntimeException('The README banner found no webdevops/php images to show.');
        }
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

    /**
     * The newest PHP version of an image with a base OS suffix, e.g. 8.5 for php-nginx-dev:8.5-alpine.
     */
    public function newestVersion(string $image, string $suffix): ?string
    {
        $versions = [];
        foreach (array_keys($this->tags[$image] ?? []) as $tag) {
            if (preg_match('/^(?P<version>\d+\.\d+)' . ($suffix === self::NO_SUFFIX ? '' : '-' . $suffix) . '$/', $tag, $match)) {
                $versions[] = $match['version'];
            }
        }
        usort($versions, fn (string $a, string $b): int => version_compare($a, $b));
        return $versions === [] ? null : end($versions);
    }

    public function hasDevVariant(): bool
    {
        return $this->hasDev;
    }

    /**
     * @return list<string> the base OS suffixes of the tags, NO_SUFFIX for tags without one
     */
    public function baseSuffixes(): array
    {
        $suffixes = array_keys($this->suffixes);
        sort($suffixes);
        return $suffixes;
    }

    /**
     * The releases in the FROM lines of webdevops/php with a base OS suffix,
     * e.g. bookworm for "FROM php:8.5-fpm-bookworm". Several if the PHP versions use different ones,
     * in the order of the PHP versions.
     *
     * @return list<string>
     */
    public function releases(string $suffix): array
    {
        $dockerfiles = $this->phpDockerfiles[$suffix] ?? [];
        uksort($dockerfiles, fn (string $a, string $b): int => version_compare($a, $b));
        $releases = [];
        foreach ($dockerfiles as $dockerfile) {
            if (!preg_match('/^FROM\s+php:\S+-(?P<release>[a-z][a-z0-9.]*)\s*$/mi', file_get_contents($dockerfile), $match)) {
                throw new RuntimeException('The README banner found no release in the FROM line of ' . $dockerfile);
            }
            $releases[] = $match['release'];
        }
        return array_values(array_unique($releases));
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
}
