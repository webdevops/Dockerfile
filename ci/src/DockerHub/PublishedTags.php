<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

use RuntimeException;
use Webdevops\Build\FileReader;

use function explode;
use function is_array;
use function is_string;
use function ksort;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strnatcmp;
use function strrpos;
use function substr;
use function usort;

/**
 * The tags that .github/workflows/build.yaml publishes, per Docker Hub repository, grouped by the operating system
 * of their base image: the tag and its aliases of every Dockerfile (FileReader::getInfo), so latest and the tags of
 * webdevops/php-official are included.
 */
final readonly class PublishedTags
{
    /**
     * @param array<string, string> $releases name of a Debian release per codename, e.g. bookworm => Debian 12
     */
    public function __construct(private FileReader $fileReader, private array $releases)
    {
    }

    /**
     * Rows of the tag table per repository name, e.g. php => [[base => Debian 12, tags => [8.5, 8.4]], ...]. The rows
     * of other systems come first, then those of Alpine, each in descending natural order of the name; the tags in
     * a row in descending natural order, latest last.
     *
     * @return array<string, list<array{base: string, tags: list<string>}>>
     */
    public function byRepository(): array
    {
        $nodes = [];
        $baseImages = [];
        foreach ($this->fileReader->collectDockerfiles() as $dockerfile) {
            $node = $this->fileReader->getInfo($dockerfile);
            $nodes[] = $node;
            foreach ($this->published($node) as $name) {
                $baseImages[$name] = is_string($node['baseImage']) ? $node['baseImage'] : '';
            }
        }

        $grouped = [];
        foreach ($nodes as $node) {
            $id = (string)$node['id'];
            $base = $this->baseName(is_string($node['baseImage']) ? $node['baseImage'] : '', $baseImages, $id);
            foreach ($this->published($node) as $name) {
                [$repository, $tag] = explode(':', substr($name, strlen('webdevops/')), 2);
                $grouped[$repository][$base][] = $tag;
            }
        }

        ksort($grouped, SORT_STRING);
        $byRepository = [];
        foreach ($grouped as $repository => $bases) {
            $rows = [];
            foreach ($bases as $base => $tags) {
                usort($tags, static fn (string $a, string $b): int => ($a === 'latest') <=> ($b === 'latest') ?: strnatcmp($b, $a));
                $rows[] = ['base' => (string)$base, 'tags' => $tags];
            }

            usort($rows, static fn (array $a, array $b): int => self::isAlpine($a['base']) <=> self::isAlpine($b['base'])
                ?: strnatcmp($b['base'], $a['base']));
            $byRepository[(string)$repository] = $rows;
        }

        return $byRepository;
    }

    /**
     * The id and the aliases of a node, e.g. webdevops/php:8.5 and webdevops/php-official:8.5.
     *
     * @param array<string, mixed> $node
     * @return list<string>
     */
    private function published(array $node): array
    {
        $names = [(string)$node['id']];
        foreach (is_array($node['aliases']) ? $node['aliases'] : [] as $alias) {
            $names[] = (string)$alias;
        }

        return $names;
    }

    /**
     * The operating system of a base image, following webdevops images to the image they are built on.
     *
     * @param array<string, string> $baseImages base image per published image
     */
    private function baseName(string $from, array $baseImages, string $id): string
    {
        $seen = [];
        while (str_starts_with($from, 'webdevops/') && !isset($seen[$from])) {
            $seen[$from] = true;
            $reference = str_contains($from, ':') ? $from : $from . ':latest';
            if (!isset($baseImages[$reference])) {
                break;
            }

            $from = $baseImages[$reference];
        }

        $name = $this->name($from);
        if ($name === null) {
            throw new RuntimeException(sprintf(
                'Cannot name the base image %s of %s for its Docker Hub page. Add its release to "releases" in conf/dockerhub.yml.',
                $from,
                $id,
            ));
        }

        return $name;
    }

    /**
     * Debian releases by their name in conf/dockerhub.yml, Alpine with the version that the reference names, Ubuntu
     * with its version, e.g. php:8.5-fpm-bookworm => Debian 12, php:8.5-fpm-alpine => Alpine,
     * alpine:3.22 => Alpine 3.22, ubuntu:22.04 => Ubuntu 22.04.
     */
    private function name(string $from): ?string
    {
        // a digest does not change the system
        $from = explode('@', $from, 2)[0];
        [$image, $tag] = str_contains($from, ':') ? explode(':', $from, 2) : [$from, 'latest'];
        switch ($image) {
            case 'php':
                $position = strrpos($tag, '-');
                $release = $position === false ? $tag : substr($tag, $position + 1);
                if (preg_match('/^alpine(\d+(?:\.\d+)*)?$/', $release, $matches) === 1) {
                    return isset($matches[1]) ? 'Alpine ' . $matches[1] : 'Alpine';
                }

                return $this->releases[$release] ?? null;
            case 'alpine':
                if ($tag === 'latest') {
                    return 'Alpine';
                }

                return preg_match('/^\d+(?:\.\d+)*$/', $tag) === 1 ? 'Alpine ' . $tag : null;
            case 'ubuntu':
                return preg_match('/^\d+\.\d+$/', $tag) === 1 ? 'Ubuntu ' . $tag : null;
            case 'debian':
                return $this->releases[explode('-', $tag, 2)[0]] ?? null;
            default:
                return null;
        }
    }

    private static function isAlpine(string $base): bool
    {
        return str_starts_with($base, 'Alpine');
    }
}
