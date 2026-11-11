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
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../..')
    {
        $this->settings = Yaml::parseFile($this->rootPath . '/conf/console.yml');
    }

    /**
     * The tag that is published as latest too, e.g. ubuntu-22.04.
     */
    public function autoLatestTag(): string
    {
        $tag = $this->settings['docker']['autoLatestTag'] ?? null;
        if (!is_string($tag) || $tag === '') {
            // An unquoted value such as 22.10 is a number in YAML and would lose its trailing zero
            throw new RuntimeException('"docker: autoLatestTag" in conf/console.yml has to be a tag in quotes, e.g. \'ubuntu-22.04\'.');
        }

        return $tag;
    }

    /**
     * The Dockerfile of every tag under docker/ (see TagDirectories), relative to the repository root and sorted, e.g.
     * docker/base/alpine/Dockerfile. Tags whose Dockerfile was not generated yet are left out. Directories that are
     * symbolic links, such as docker/php/8.5, are left out too: they build the same Dockerfile.
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
     * The build tree node of a generated Dockerfile: image, tag, parent image and test settings.
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
        $regex = '/' . $this->settings['dockerTest']['configuration']['imageConfigurationRegex'] . '/';
        preg_match_all($regex, $id, $serverSpecMatches);
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
            'serverspec' => [
                'DOCKER_IMAGE' => $id,
                'DOCKER_TAG' => $tagName,
                'OS_FAMILY' => $serverSpecMatches['OS_FAMILY'][0] ?? $this->settings['dockerTest']['configuration']['default']['OS_FAMILY'],
                'OS_VERSION' => $serverSpecMatches['OS_VERSION'][0] ?? $this->settings['dockerTest']['configuration']['default']['OS_VERSION'],
            ],
        ];
        // Additional serverSpec variables (only first match)
        foreach ($this->settings['dockerTest']['configuration']['image'] as $regex => $variables) {
            if (preg_match('#' . $regex . '#i', $id)) {
                $node['serverspec'] = array_merge($node['serverspec'], $variables);
                break;
            }
        }

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

        // Treat *-official images
        if (str_contains($id, '-official:')) {
            $node['aliases'][] = $id;
            $node['id'] = $node['name'] = str_replace('-official:', ':', $id);
            $node['image'] = str_replace('-official', '', $node['image']);
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
     * and exports it. A few images (e.g. toolbox, dockerfile-build-env) *do* have a
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
        if ($tag === 'latest' && !$dockerfileExists($tag) && $dockerfileExists($this->autoLatestTag())) {
            $tag = $this->autoLatestTag();
        }

        return 'webdevops/' . $image . ':' . $tag;
    }
}
