<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;

use function array_keys;
use function array_map;
use function count;
use function end;
use function explode;
use function in_array;
use function preg_match;
use function sort;
use function sprintf;
use function strtolower;
use function str_ends_with;
use function strrpos;
use function substr;
use function uksort;
use function usort;
use function version_compare;

/**
 * What the README banner shows about the PHP images, taken from the images
 * the CI builds: web server flavors, PHP versions, base OS suffixes with
 * their releases, and the platforms.
 */
final class ImageMatrix
{
    /** Base OS of tags without a suffix, e.g. 8.5 */
    public const NO_SUFFIX = '';

    /** Parts of an official PHP image tag that come before the release, e.g. fpm in 8.5-fpm-bookworm */
    private const PHP_IMAGE_VARIANTS = ['apache', 'cli', 'fpm', 'rc', 'zts'];

    /** @var array<string, true> */
    private array $flavors = [];
    /** @var array<string, true> */
    private array $versions = [];
    /** @var array<string, array<string, list<string>>> PHP versions per image and base OS suffix, e.g. php-nginx-dev => alpine => [8.5] */
    private array $imageVersions = [];
    /** @var array<string, true> */
    private array $suffixes = [];
    /** @var array<string, array<string, ?string>> image webdevops/php is built from, per base OS suffix and PHP version */
    private array $phpBaseImages = [];
    /** @var array<string, array<string, string>> releases() per base OS suffix, read once */
    private array $releases = [];

    /**
     * @param list<array{image: string, tag: string, from: ?string}> $images published images, e.g. php-nginx-dev with tag 8.5-alpine,
     *                                                                and the image of their last FROM line
     * @param list<string> $platforms e.g. linux/amd64
     */
    public function __construct(array $images, private readonly array $platforms)
    {
        foreach ($images as $image) {
            if (!preg_match('/^php(-[a-z0-9]+)*$/', $image['image'])) {
                continue;
            }
            // Every PHP tag is shown, so a tag the banner cannot take apart stops it instead of being left out
            if (!preg_match('/^(?P<version>\d+\.\d+)(?:-(?P<suffix>.+))?$/', $image['tag'], $tag)) {
                throw new RuntimeException(sprintf(
                    'The README banner cannot take apart the tag webdevops/%s:%s. It expects the PHP version and an optional suffix, e.g. 8.5 or 8.5-alpine.',
                    $image['image'],
                    $image['tag'],
                ));
            }
            $flavor = str_ends_with($image['image'], '-dev') ? substr($image['image'], 0, -4) : $image['image'];
            $suffix = $tag['suffix'] ?? self::NO_SUFFIX;
            $this->flavors[$flavor] = true;
            $this->versions[$tag['version']] = true;
            $this->suffixes[$suffix] = true;
            $this->imageVersions[$image['image']][$suffix][] = $tag['version'];
            if ($image['image'] === 'php') {
                // The other PHP images build on webdevops/php, whose base image names the release
                $this->phpBaseImages[$suffix][$tag['version']] = $image['from'];
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
        $versions = $this->imageVersions[$image][$suffix] ?? [];
        usort($versions, fn (string $a, string $b): int => version_compare($a, $b));
        return $versions === [] ? null : end($versions);
    }

    /**
     * @return list<string> the base OS suffixes of the tags, NO_SUFFIX for tags without one
     */
    public function baseSuffixes(): array
    {
        // Array keys turn numeric suffixes such as "1" into integers
        $suffixes = array_map('strval', array_keys($this->suffixes));
        sort($suffixes);
        return $suffixes;
    }

    /**
     * The release of the base image of webdevops/php per PHP version, e.g. 8.5 => bookworm
     * for "FROM php:8.5-fpm-bookworm", oldest version first.
     *
     * @return array<string, string>
     */
    public function releases(string $suffix): array
    {
        if (!isset($this->releases[$suffix])) {
            $baseImages = $this->phpBaseImages[$suffix] ?? [];
            uksort($baseImages, fn (string $a, string $b): int => version_compare($a, $b));
            $this->releases[$suffix] = [];
            foreach ($baseImages as $version => $baseImage) {
                $tag = (string)$version . ($suffix === self::NO_SUFFIX ? '' : '-' . $suffix);
                $this->releases[$suffix][(string)$version] = $this->releaseOf($baseImage, $tag);
            }
        }
        return $this->releases[$suffix];
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

    /**
     * The release at the end of an official PHP image tag, which has the form
     * <version>[-<variant>]-<release>: bookworm for php:8.5-fpm-bookworm, also with a registry or a digest.
     */
    private function releaseOf(?string $baseImage, string $tag): string
    {
        // Leave out the digest and the registry, which may have a port
        $name = explode('@', (string)$baseImage, 2)[0];
        $name = substr($name, (int)strrpos($name, '/'));
        $colon = strrpos($name, ':');
        $parts = $colon === false ? [] : explode('-', substr($name, $colon + 1));
        $release = count($parts) > 1 ? end($parts) : '';
        if (in_array(strtolower($release), self::PHP_IMAGE_VARIANTS, true) || !preg_match('/^[a-z][a-z0-9.]*$/i', $release)) {
            throw new RuntimeException(sprintf(
                'The README banner found no release in "%s", the image webdevops/php:%s is built from. Name it in the tag, e.g. php:8.5-fpm-bookworm.',
                (string)$baseImage,
                $tag,
            ));
        }
        return $release;
    }
}
