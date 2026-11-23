<?php

namespace Webdevops\Build;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class FileReader
{
    /**
     * @var array<string, mixed> conf/console.yml
     */
    private readonly array $settings;

    /**
     * @var ?array<string, array{image: string, tags: list<string>}> aliases(), read on first use
     */
    private ?array $aliases = null;

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../..')
    {
        $this->settings = Yaml::parseFile($this->rootPath . '/conf/console.yml');
    }

    /**
     * The tag that is published as latest too, e.g. ubuntu-22.04; null if conf/console.yml sets ~, so that no tag is.
     */
    public function autoLatestTag(): ?string
    {
        $docker = $this->settings['docker'] ?? null;
        if (is_array($docker) && array_key_exists('autoLatestTag', $docker) && $docker['autoLatestTag'] === null) {
            return null;
        }

        $tag = is_array($docker) ? $docker['autoLatestTag'] ?? null : null;
        if (!is_string($tag) || $tag === '') {
            // An unquoted value such as 22.10 is a number in YAML and would lose its trailing zero
            throw new RuntimeException('"docker: autoLatestTag" in conf/console.yml has to be a tag in quotes, e.g. \'ubuntu-22.04\', or ~ for none.');
        }

        return $tag;
    }

    /**
     * "docker: aliases" in conf/console.yml: the deprecated image names that the CI also publishes an image under, each
     * with that image and the tags it is published for, e.g. ['php-official' => ['image' => 'php', 'tags' => ['8.5']]].
     * A deprecated name gets no new tags: it is only published for the tags listed there.
     *
     * @return array<string, array{image: string, tags: list<string>}>
     */
    public function aliases(): array
    {
        return $this->aliases ??= $this->readAliases();
    }

    /**
     * @return array<string, array{image: string, tags: list<string>}>
     */
    private function readAliases(): array
    {
        $settings = $this->settings['docker']['aliases'] ?? [];
        if (!is_array($settings)) {
            throw new RuntimeException('"docker: aliases" in conf/console.yml has to map each deprecated name to the image it names and its tags, e.g. php-official: {image: php, tags: [\'8.5\']}.');
        }

        $aliases = [];
        foreach ($settings as $name => $alias) {
            $image = is_array($alias) ? $alias['image'] ?? null : null;
            $tags = is_array($alias) ? $alias['tags'] ?? null : null;
            $problem = match (true) {
                !is_string($name) || !$this->isImageName($name) => 'the deprecated name has to be an image name in lower case, e.g. php-official',
                !is_string($image) || !$this->isImageName($image) => '"image" has to name the image in lower case, e.g. php',
                $name === $image => 'the deprecated name has to differ from its image',
                // an unquoted tag such as 8.10 is a number in YAML and would lose its trailing zero
                !is_array($tags) || !array_is_list($tags) || array_any($tags, static fn (mixed $tag): bool => !is_string($tag) || $tag === '') => '"tags" has to be a list of tags in quotes, e.g. [\'8.5\']',
                count(array_unique($tags)) !== count($tags) => '"tags" lists a tag twice',
                default => null,
            };
            if ($problem !== null) {
                throw new RuntimeException(sprintf('"docker: aliases: %s" in conf/console.yml: %s.', $name, $problem));
            }

            /** @var string $name */
            /** @var string $image */
            /** @var list<string> $tags */
            $aliases[$name] = ['image' => $image, 'tags' => $tags];
        }

        return $aliases;
    }

    /**
     * Whether $name can be the name of a webdevops image, e.g. php-official: lower case letters and digits, separated
     * by single dots, dashes or underscores.
     */
    private function isImageName(string $name): bool
    {
        return (bool)preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $name);
    }

    /**
     * The Dockerfile of every tag under docker/ (see TagDirectories), relative to the repository root and sorted, e.g.
     * docker/base/alpine/Dockerfile. Tags whose Dockerfile was not generated yet are left out.
     *
     * @return list<string>
     */
    public function collectDockerfiles(): array
    {
        $dockerfiles = [];
        foreach (new TagDirectories($this->rootPath)->tags() as $directory) {
            if (is_file($this->rootPath . '/' . $directory . '/Dockerfile')) {
                $dockerfiles[] = $directory . '/Dockerfile';
            }
        }

        return $dockerfiles;
    }

    /**
     * The build tree node of a generated Dockerfile: image, tag and parent image.
     *
     * @param string $dockerfile path relative to the repository root, e.g. docker/base/alpine/Dockerfile
     * @return array<string, mixed>
     */
    public function getInfo(string $dockerfile): array
    {
        $path = $this->rootPath . '/' . $dockerfile;
        $content = is_file($path) ? file_get_contents($path) : false;
        if ($content === false) {
            throw new RuntimeException(sprintf('Cannot read %s.', $dockerfile));
        }

        // Extract info from file header
        if (!preg_match('/# Dockerfile for webdevops\/(.*):(.*)/', $content, $headerMatches)) {
            throw new RuntimeException(sprintf('%s has no "# Dockerfile for webdevops/<image>:<tag>" header.', $dockerfile));
        }

        $imageName = $headerMatches[1];
        $tagName = $headerMatches[2];
        $id = 'webdevops/' . $imageName . ':' . $tagName;
        $node = [
            'id' => $id,
            'name' => $id,
            'image' => $imageName,
            'tag' => $tagName,
            'aliases' => [],
            'file' => $dockerfile,
            'parent' => 0,
            'imageParent' => 0,
            'imageParentRef' => null,
            'imageDependencies' => [],
            // Image of the last FROM line, which the final stage builds on, e.g. "php:8.5-fpm-bookworm"
            'baseImage' => null,
        ];

        // Only internal images must be contained in build tree
        preg_match_all('/^FROM\s+(?:--\S+\s+)*(\S+)(?:\s+AS\s+\S+)?\s*$/mi', $content, $fromMatches);
        $parentImage = array_pop($fromMatches[1]);
        $node['baseImage'] = $parentImage;
        if ($parentImage !== null && str_starts_with($parentImage, 'webdevops/')) {
            // Real Docker image inheritance: the Dockerfile's FROM references
            // another internal webdevops/* image. This is the only case where
            // 'imageParent' driven OCI artifact propagation (build-contexts
            // override) is valid. 'imageParentRef' keeps the *exact* literal
            // text used in the FROM statement (e.g. "webdevops/base:latest"),
            // because that is the key BuildKit matches against when a
            // `build-contexts` override is supplied; it must NOT be the
            // resolved/aliased image id, or the override silently fails to
            // apply and the build falls back to pulling the published image.
            $node['parent'] = $this->resolveInternalImageReference($parentImage);
            $node['imageParent'] = $node['parent'];
            $node['imageParentRef'] = $parentImage;
        } elseif ($node['id'] !== 'webdevops/toolbox:latest') {
            // Synthetic scheduling dependency only (e.g. to serialize CI
            // against the Toolbox job). The Dockerfile does NOT actually
            // build FROM this image, so it must never drive OCI artifact
            // download/upload or build-contexts overrides.
            $node['parent'] = 'webdevops/toolbox:latest';
        }

        // Additional internal image dependencies referenced via
        // `COPY --from=webdevops/...` (not a FROM parent). BuildKit's named
        // build-context override mechanism applies identically to
        // `COPY --from=<name>` references, so these need the exact same OCI
        // artifact propagation as a real FROM parent, otherwise the copied
        // files still come from the published registry image.
        preg_match_all('/^COPY\s+--from=(webdevops\/\S+)/m', $content, $copyFromMatches);
        foreach (array_unique($copyFromMatches[1]) as $dependencyRef) {
            $node['imageDependencies'][$dependencyRef] = $this->resolveInternalImageReference($dependencyRef);
        }

        // deprecated names of the image, e.g. webdevops/php-official:8.5 for webdevops/php:8.5
        foreach ($this->aliases() as $name => $alias) {
            if ($alias['image'] === $imageName && in_array($tagName, $alias['tags'], true)) {
                $node['aliases'][] = 'webdevops/' . $name . ':' . $tagName;
            }
        }

        if ($tagName === $this->autoLatestTag()) {
            $node['aliases'][] = str_replace(':' . $tagName, ':latest', $id);
        }

        return $node;
    }

    /**
     * Resolve a literal internal `webdevops/<image>[:<tag>]` reference, as it
     * appears in a `FROM` or `COPY --from=` statement, to the exact image id
     * that is actually built by this repository's CI pipeline (i.e. the id
     * backed by a real `docker/<image>/<tag>/Dockerfile`).
     *
     * This is the single source of truth for resolving a `:latest` (or
     * untagged, which Docker treats identically) reference: most images do
     * not have a literal "latest" subdirectory, so `:latest` is only a
     * published alias for whichever folder `autoLatestTag` points to, and a
     * reference like "webdevops/base:latest" must resolve to
     * "webdevops/base:ubuntu-22.04" to match the job that actually builds
     * and exports it. A few images (e.g. toolbox) *do* have a
     * literal "latest" subdirectory and must be left untouched. Checking the
     * filesystem directly, rather than assuming the "latest" alias
     * substitution always applies, keeps this correct for both cases.
     */
    private function resolveInternalImageReference(string $reference): string
    {
        $imageAndTag = substr($reference, strlen('webdevops/'));
        if (str_contains($imageAndTag, ':')) {
            [$image, $tag] = explode(':', $imageAndTag, 2);
        } else {
            // `COPY --from=webdevops/toolbox` has no explicit tag; Docker
            // treats an untagged reference as `:latest`.
            $image = $imageAndTag;
            $tag = 'latest';
        }

        $dockerfileExists = fn (string $tag): bool => file_exists(
            $this->rootPath . '/docker/' . $image . '/' . $tag . '/Dockerfile',
        );
        $latestTag = $this->autoLatestTag();
        if ($tag === 'latest' && !$dockerfileExists($tag) && $latestTag !== null && $dockerfileExists($latestTag)) {
            $tag = $latestTag;
        }

        return 'webdevops/' . $image . ':' . $tag;
    }
}
