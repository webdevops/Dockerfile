<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function array_intersect_key;
use function array_keys;
use function array_values;
use function is_array;
use function is_bool;
use function is_string;
use function ksort;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * conf/dockerhub.yml: what the Docker Hub pages say that the Dockerfiles do not, for the images that are built and for
 * those that are not built any more.
 *
 * @phpstan-type Link array{label: string, path: string}
 * @phpstan-type Example array{lang: string, code: string, tag: ?string}
 * @phpstan-type BuiltEntry array{short: string, intro: string, docs: list<Link>, example: ?Example, development: bool, note: ?string}
 * @phpstan-type Replacement array{image: string, url: string, docs: string}
 * @phpstan-type DiscontinuedEntry array{lastBuilt: string, replacement: ?Replacement}
 */
final readonly class Config
{
    public const string FILE = 'conf/dockerhub.yml';

    /**
     * Docker Hub takes at most this many bytes as the short description of a repository.
     */
    public const int SHORT_DESCRIPTION_LIMIT = 100;

    /**
     * @var array<string, BuiltEntry> per repository name, e.g. php-nginx
     */
    private array $built;

    /**
     * @var array<string, DiscontinuedEntry> per repository name, e.g. nginx
     */
    private array $discontinued;

    /**
     * @var array<string, string> name of a Debian release per codename, e.g. bookworm => Debian 12
     */
    private array $releases;

    /**
     * @var list<Repository> sorted by name
     */
    private array $repositories;

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(string $rootPath)
    {
        $settings = Yaml::parseFile($rootPath . '/' . self::FILE);
        $settings = is_array($settings) ? $settings : [];

        $releases = [];
        foreach ($this->map($settings, 'releases', self::FILE) as $codename => $name) {
            $releases[(string)$codename] = $this->string($name, sprintf('"releases: %s" in conf/dockerhub.yml has to be a name, e.g. \'Debian 12\'.', $codename));
        }

        $built = [];
        foreach ($this->map($settings, 'built', self::FILE) as $name => $entry) {
            $built[(string)$name] = $this->builtEntry((string)$name, $entry);
        }

        $discontinued = [];
        foreach ($this->map($settings, 'discontinued', self::FILE) as $name => $entry) {
            $discontinued[(string)$name] = $this->discontinuedEntry((string)$name, $entry);
        }

        foreach (array_keys(array_intersect_key($built, $discontinued)) as $name) {
            throw new RuntimeException(sprintf('webdevops/%s is listed under both "built" and "discontinued" in conf/dockerhub.yml.', $name));
        }

        $repositories = [];
        foreach ($built as $name => $entry) {
            $repositories[$name] = new Repository($name, $entry['short'], true);
        }

        foreach ($discontinued as $name => $entry) {
            $short = 'No longer built or updated.';
            if ($entry['replacement'] !== null) {
                $short .= ' Use ' . $entry['replacement']['image'] . ' instead.';
            }

            $repositories[$name] = new Repository($name, $short, false);
        }

        ksort($repositories, SORT_STRING);
        foreach ($repositories as $repository) {
            if (strlen($repository->shortDescription) > self::SHORT_DESCRIPTION_LIMIT) {
                throw new RuntimeException(sprintf(
                    'The short description of webdevops/%s has %d bytes; Docker Hub takes at most %d.',
                    $repository->name,
                    strlen($repository->shortDescription),
                    self::SHORT_DESCRIPTION_LIMIT,
                ));
            }
        }

        $this->built = $built;
        $this->discontinued = $discontinued;
        $this->releases = $releases;
        $this->repositories = array_values($repositories);
    }

    /**
     * Every repository, the built ones and the discontinued ones, sorted by name.
     *
     * @return list<Repository>
     */
    public function repositories(): array
    {
        return $this->repositories;
    }

    /**
     * @return array<string, BuiltEntry>
     */
    public function built(): array
    {
        return $this->built;
    }

    /**
     * @return array<string, DiscontinuedEntry>
     */
    public function discontinued(): array
    {
        return $this->discontinued;
    }

    /**
     * @return array<string, string>
     */
    public function releases(): array
    {
        return $this->releases;
    }

    /**
     * @return BuiltEntry
     */
    private function builtEntry(string $name, mixed $entry): array
    {
        $entry = is_array($entry) ? $entry : [];
        $docs = [];
        foreach ($this->map($entry, 'docs', 'webdevops/' . $name . ' in ' . self::FILE) as $link) {
            $link = is_array($link) ? $link : [];
            $docs[] = [
                'label' => $this->required($link, 'label', $name, 'docs: label'),
                'path' => $this->required($link, 'path', $name, 'docs: path'),
            ];
        }

        if ($docs === []) {
            throw new RuntimeException(sprintf('webdevops/%s in conf/dockerhub.yml needs "docs".', $name));
        }

        $example = null;
        if (isset($entry['example'])) {
            $example = is_array($entry['example']) ? $entry['example'] : [];
            $example = [
                'lang' => $this->required($example, 'lang', $name, 'example: lang'),
                'code' => $this->required($example, 'code', $name, 'example: code'),
                'tag' => isset($example['tag']) ? $this->required($example, 'tag', $name, 'example: tag') : null,
            ];
        }

        $development = $entry['development'] ?? false;
        if (!is_bool($development)) {
            throw new RuntimeException(sprintf('"development" of webdevops/%s in conf/dockerhub.yml has to be true or false.', $name));
        }

        return [
            'short' => $this->required($entry, 'short', $name, 'short'),
            'intro' => $this->required($entry, 'intro', $name, 'intro'),
            'docs' => $docs,
            'example' => $example,
            'development' => $development,
            'note' => isset($entry['note']) ? $this->required($entry, 'note', $name, 'note') : null,
        ];
    }

    /**
     * @return DiscontinuedEntry
     */
    private function discontinuedEntry(string $name, mixed $entry): array
    {
        $entry = is_array($entry) ? $entry : [];
        $lastBuilt = $entry['lastBuilt'] ?? null;
        if (!is_string($lastBuilt) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $lastBuilt) !== 1) {
            throw new RuntimeException(sprintf("lastBuilt of webdevops/%s in conf/dockerhub.yml has to be a month in quotes, e.g. '2026-11'.", $name));
        }

        $replacement = null;
        if (isset($entry['replacement'])) {
            $replacement = is_array($entry['replacement']) ? $entry['replacement'] : [];
            $replacement = [
                'image' => $this->required($replacement, 'image', $name, 'replacement: image'),
                'url' => $this->required($replacement, 'url', $name, 'replacement: url'),
                'docs' => $this->required($replacement, 'docs', $name, 'replacement: docs'),
            ];
        }

        return ['lastBuilt' => $lastBuilt, 'replacement' => $replacement];
    }

    /**
     * @param array<mixed> $settings
     * @return array<mixed>
     */
    private function map(array $settings, string $key, string $where): array
    {
        $value = $settings[$key] ?? null;
        if (!is_array($value) || $value === []) {
            throw new RuntimeException(sprintf('%s needs "%s".', $where, $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $entry
     */
    private function required(array $entry, string $key, string $name, string $label): string
    {
        $value = $entry[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('webdevops/%s in conf/dockerhub.yml needs "%s".', $name, $label));
        }

        return $value;
    }

    private function string(mixed $value, string $message): string
    {
        if (!is_string($value) || $value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }
}
