<?php

namespace Webdevops\Build;

use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveDirectoryIterator;
use RecursiveRegexIterator;
use Symfony\Component\Yaml\Yaml;

class FileReader
{
    private $_settings;

    public function __construct()
    {
        $this->_settings = Yaml::parseFile(__DIR__ . '/../../conf/console.yml');
    }

    public function collectDockerfiles()
    {
        $dockerDir = new RecursiveDirectoryIterator(__DIR__ . '/../../docker/');
        $iterator = new RecursiveIteratorIterator($dockerDir);
        return new RegexIterator($iterator, '/^.*Dockerfile$/i', RecursiveRegexIterator::GET_MATCH);
    }

    public function getInfo(string $dockerfilePath)
    {
        $content = file_get_contents($dockerfilePath);
        // Extract info from file header
        preg_match('/# Dockerfile for webdevops\/(.*):(.*)/', $content, $headerMatches);
        $imageName = $headerMatches[1];
        $tagName = $headerMatches[2];
        $id = 'webdevops/' . $imageName . ':' . $tagName;
        $regex = '/' . $this->_settings['dockerTest']['configuration']['imageConfigurationRegex'] . '/';
        preg_match_all($regex, $id, $serverSpecMatches);
        $node = [
            'id' => $id,
            'name' => $id,
            'image' => $imageName,
            'tag' => $tagName,
            'aliases' => [],
            'file' => $dockerfilePath,
            'parent' => 0,
            'imageParent' => 0,
            'imageParentRef' => 0,
            'imageDependencies' => [],
            'serverspec' => [
                'DOCKER_IMAGE' => $id,
                'DOCKER_TAG' => $tagName,
                'OS_FAMILY' => $serverSpecMatches['OS_FAMILY'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_FAMILY'],
                'OS_VERSION' => $serverSpecMatches['OS_VERSION'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_VERSION'],
            ],
        ];
        // Additional serverSpec variables (only first match)
        foreach ($this->_settings['dockerTest']['configuration']['image'] as $regex => $variables) {
            if (preg_match('#' . $regex . '#i', $id)) {
                $node['serverspec'] = array_merge($node['serverspec'], $variables);
                break;
            }
        }
        // Only internal images must be contained in build tree
        preg_match_all('/^FROM\s+(\S+)(?:\s+AS\s+\S+)?\s*$/mi', $content, $fromMatches);
        $parentImage = array_pop($fromMatches[1]);
        if (strpos($parentImage, 'webdevops/') === 0) {
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
        } else if ($node['id'] !== 'webdevops/toolbox:latest') {
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
        if (strpos($id, '-official:') !== false) {
            $node['aliases'][] = $id;
            $node['id'] = $node['name'] = str_replace('-official:', ':', $id);
            $node['image'] = str_replace('-official', '', $node['image']);
        }
        if ($tagName === $this->_settings['docker']['autoLatestTag']) {
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
     * Most images do not have a literal "latest" subdirectory: `:latest` is
     * only a published alias for whichever folder `autoLatestTag` points to
     * (see the alias handling above), so a reference like
     * "webdevops/base:latest" must resolve to "webdevops/base:ubuntu-22.04"
     * to match the job that actually builds and exports it. A few images
     * (e.g. toolbox, ssh, vsftp) *do* have a literal "latest" subdirectory
     * and must be left untouched. Checking the filesystem directly, rather
     * than assuming the "latest" alias substitution always applies, keeps
     * this correct for both cases.
     */
    private function resolveInternalImageReference(string $reference): string
    {
        $imageAndTag = substr($reference, strlen('webdevops/'));
        if (strpos($imageAndTag, ':') !== false) {
            [$image, $tag] = explode(':', $imageAndTag, 2);
        } else {
            // `COPY --from=webdevops/toolbox` has no explicit tag; Docker
            // treats an untagged reference as `:latest`.
            $image = $imageAndTag;
            $tag = 'latest';
        }
        $dockerfileExists = fn (string $tag): bool => file_exists(
            __DIR__ . '/../../docker/' . $image . '/' . $tag . '/Dockerfile',
        );
        if ($tag === 'latest' && !$dockerfileExists($tag) && $dockerfileExists($this->_settings['docker']['autoLatestTag'])) {
            $tag = $this->_settings['docker']['autoLatestTag'];
        }
        return 'webdevops/' . $image . ':' . $tag;
    }

}
