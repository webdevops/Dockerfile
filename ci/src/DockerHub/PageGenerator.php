<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

use DateTimeImmutable;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Webdevops\Build\FileReader;
use Webdevops\Build\GithubJobBuilder;

use function array_column;
use function array_diff;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function basename;
use function file_put_contents;
use function glob;
use function implode;
use function in_array;
use function is_dir;
use function mkdir;
use function rtrim;
use function sprintf;
use function str_replace;
use function strlen;
use function unlink;

/**
 * Writes the Docker Hub page of every repository in conf/dockerhub.yml to docs/dockerhub/<repository>.md: for an
 * image that is built a short start page with its tags, for the others a warning that they get no updates. The job
 * GithubJobBuilder::DOCKER_HUB_JOB uploads the pages.
 *
 * Every check runs before the first file is written: an image under docker/ without an entry, an entry of an image
 * that is not built, a link to a file or heading that does not exist, a page too large for Docker Hub.
 */
final readonly class PageGenerator
{
    public const string DIRECTORY = Repository::DIRECTORY;

    /**
     * Docker Hub takes at most this many bytes as the description of a repository.
     */
    public const int PAGE_LIMIT = 25000;

    /**
     * The templates belong to the generator, not to the repository it writes into.
     */
    private const string TEMPLATES = __DIR__ . '/../../resources/dockerhub';

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath = __DIR__ . '/../../..')
    {
    }

    /**
     * @return array{written: list<string>, removed: list<string>} paths relative to the repository root
     */
    public function generate(): array
    {
        $config = new Config($this->rootPath);
        $tags = new PublishedTags(new FileReader($this->rootPath), $config->releases())->byRepository();
        $built = $config->built();
        $this->checkEntries(array_keys($tags), array_keys($built));

        $twig = new Environment(new FilesystemLoader(self::TEMPLATES), ['autoescape' => false, 'strict_variables' => true]);
        $links = new Links($this->rootPath);
        $pages = [];
        foreach ($config->repositories() as $repository) {
            $page = $repository->built
                ? $this->builtPage($twig, $links, $repository->name, $built[$repository->name], $tags[$repository->name])
                : $this->discontinuedPage($twig, $links, $repository->name, $config->discontinued()[$repository->name]);
            if (strlen($page) > self::PAGE_LIMIT) {
                throw new RuntimeException(sprintf(
                    '%s has %d bytes; Docker Hub takes at most %d.',
                    $repository->pagePath(),
                    strlen($page),
                    self::PAGE_LIMIT,
                ));
            }

            $pages[$repository->pagePath()] = $page;
        }

        return ['written' => array_keys($pages), 'removed' => $this->write($pages)];
    }

    /**
     * Every built image has an entry under "built", and every entry there an image that is built.
     *
     * @param list<string> $builtImages repositories with tags under docker/
     * @param list<string> $entries repositories under "built" in conf/dockerhub.yml
     */
    private function checkEntries(array $builtImages, array $entries): void
    {
        foreach (array_diff($builtImages, $entries) as $name) {
            throw new RuntimeException(sprintf('webdevops/%s is built but has no entry under "built" in conf/dockerhub.yml.', $name));
        }

        foreach (array_diff($entries, $builtImages) as $name) {
            throw new RuntimeException(sprintf(
                'webdevops/%s has an entry under "built" in conf/dockerhub.yml but is not built any more. Move it to "discontinued".',
                $name,
            ));
        }
    }

    /**
     * Writes the pages and removes the other pages in the directory.
     *
     * @param array<string, string> $pages content per path relative to the repository root
     * @return list<string> the removed pages
     */
    private function write(array $pages): array
    {
        $directory = $this->rootPath . '/' . self::DIRECTORY;
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            throw new RuntimeException(sprintf('Cannot create %s.', self::DIRECTORY));
        }

        foreach ($pages as $path => $page) {
            if (file_put_contents($this->rootPath . '/' . $path, $page) === false) {
                throw new RuntimeException(sprintf('Cannot write %s.', $path));
            }
        }

        $existing = array_map(static fn (string $file): string => self::DIRECTORY . '/' . basename($file), glob($directory . '/*.md') ?: []);
        $removed = array_values(array_diff($existing, array_keys($pages)));
        foreach ($removed as $path) {
            unlink($this->rootPath . '/' . $path);
        }

        return $removed;
    }

    /**
     * @param array{short: string, intro: string, docs: list<array{label: string, path: string}>, example: ?array{lang: string, code: string, tag: ?string}, development: bool, note: ?string} $entry
     * @param list<array{base: string, tags: list<string>}> $rows
     */
    private function builtPage(Environment $twig, Links $links, string $name, array $entry, array $rows): string
    {
        $example = $entry['example'];
        if ($example !== null) {
            $tags = array_merge(...array_column($rows, 'tags'));
            $tag = $example['tag'] ?? $rows[0]['tags'][0];
            if (!in_array($tag, $tags, true)) {
                throw new RuntimeException(sprintf(
                    'The example of webdevops/%s in conf/dockerhub.yml uses the tag %s, which is not built.',
                    $name,
                    $tag,
                ));
            }

            $example = ['lang' => $example['lang'], 'code' => rtrim(str_replace('{image}', 'webdevops/' . $name . ':' . $tag, $example['code']))];
        }

        return $twig->render('built.md.twig', [
            'name' => $name,
            'intro' => $links->rewrite($entry['intro']),
            'development' => $entry['development'],
            'note' => $entry['note'] === null ? null : $links->rewrite($entry['note']),
            'platforms' => array_column(GithubJobBuilder::PLATFORMS, 'platform'),
            'rows' => array_map(static fn (array $row): array => [
                'tags' => implode(', ', array_map(static fn (string $tag): string => '`' . $tag . '`', $row['tags'])),
                'base' => $row['base'],
            ], $rows),
            'example' => $example,
            'docs' => array_map(static fn (array $link): array => ['label' => $link['label'], 'url' => $links->url($link['path'])], $entry['docs']),
        ]);
    }

    /**
     * @param array{lastBuilt: string, replacement: ?array{image: string, url: string, docs: string}} $entry
     */
    private function discontinuedPage(Environment $twig, Links $links, string $name, array $entry): string
    {
        $replacement = $entry['replacement'];
        if ($replacement !== null) {
            $replacement['docs'] = $links->url($replacement['docs']);
        }

        $month = DateTimeImmutable::createFromFormat('!Y-m', $entry['lastBuilt']);

        return $twig->render('discontinued.md.twig', [
            'name' => $name,
            'lastBuilt' => $month === false ? $entry['lastBuilt'] : $month->format('F Y'),
            'replacement' => $replacement,
        ]);
    }
}
