<?php

namespace Webdevops\Build;

use function array_filter;
use function array_values;
use function dirname;
use function implode;
use function str_replace;

class GithubJobBuilder
{
    /**
     * Tag/annotation used when exporting a parent image as an OCI layout so
     * that child jobs can deterministically reference the exported manifest
     * instead of relying on Buildx picking an arbitrary entry from index.json.
     */
    private const PARENT_OCI_TAG = 'ci-parent-image';

    /**
     * Same-run parent artifact propagation (download/build-contexts
     * override/export/upload) only ever runs for `pull_request` builds.
     * Plain branch pushes (including master) and other trigger types (cron,
     * workflow_dispatch) fall back to pulling the published parent image, to
     * avoid doubling OCI artifact storage/network cost on every commit that
     * is covered by both a `push` and a `pull_request` workflow run. Master
     * publishing is unaffected, since master is only ever reached by `push`.
     */
    private const PR_ONLY_IF = '${{ github.event_name == \'pull_request\' }}';

    /**
     * Logical negation of {@see self::PR_ONLY_IF}, kept as its own constant
     * (rather than an inline literal) so the two conditions can never drift
     * out of sync if the gating logic above ever changes.
     */
    private const PR_ONLY_IF_NOT = '${{ github.event_name != \'pull_request\' }}';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getJobsDescription(array $node): array
    {
        $serverSpec = $this->serverSpec($node);
        $structuredTests = $this->structuredTests($node);

        $jobId = GithubJobBuilder::toJobId($node['name']);
        $hasParent = (bool)($node['parent'] ?? null);
        $imageDependencies = $this->imageDependencies($node);
        $hasImageDependencies = !empty($imageDependencies);
        $hasChildren = !empty($node['hasChildren']);
        $parentJobId = $hasParent ? GithubJobBuilder::toJobId($node['parent']) : null;
        $needs = $hasParent ? $parentJobId . '_publish' : 'validate-automation';

        $pushTags = [];
        $pushTags[] = '-t "' . $node['id'] . '"';
        $pushTags[] = '-t "ghcr.io/' . $node['id'] . '"';
        foreach ($node['aliases'] as $alias) {
            $pushTags[] = '-t "' . $alias . '"';
            $pushTags[] = '-t "ghcr.io/' . $alias . '"';
        }
        return [
            $jobId => [
                'strategy' => [
                    'fail-fast' => false,
                    'matrix' => [
                        'include' => [
                            [
                                'arch' => 'amd64',
                                'runner' => 'ubuntu-24.04',
                                'platform' => 'linux/amd64',
                            ],
                            [
                                'arch' => 'arm64',
                                'runner' => 'ubuntu-24.04-arm',
                                'platform' => 'linux/arm64',
                            ],
                        ],
                    ],
                ],
                'name' => $node['name'] . ' (${{ matrix.arch }})',
                'needs' => $needs,
                // even run if previous job skipped
                'if' => '${{ !failure() && !cancelled() }}',
                'runs-on' => '${{ matrix.runner }}',
                'container' => 'webdevops/dockerfile-build-env',
                'steps' => array_values(
                    array_filter(
                        [
                            ['uses' => 'actions/checkout@v6'],
                            ['uses' => 'docker/setup-buildx-action@v3'],
                            ...$this->downloadParentImageSteps($imageDependencies),
                            array_filter([
                                'name' => 'Build (load locally)',
                                'if' => $hasImageDependencies ? self::PR_ONLY_IF_NOT : null,
                                'uses' => 'docker/build-push-action@v6',
                                'with' => $this->buildPushWith($node),
                            ], fn ($value): bool => $value !== null),
                            $hasImageDependencies ? [
                                'name' => 'Build (load locally, from parent artifact)',
                                'if' => self::PR_ONLY_IF,
                                'uses' => 'docker/build-push-action@v6',
                                'with' => array_merge(
                                    $this->buildPushWith($node),
                                    ['build-contexts' => $this->buildContexts($imageDependencies)],
                                ),
                            ] : null,
                            $serverSpec ? [
                                'name' => 'run serverspec',
                                'run' => implode("\n", $serverSpec),
                            ] : null,
                            $structuredTests ? [
                                'name' => 'run structure-test',
                                'run' => implode("\n", $structuredTests),
                            ] : null,
                            [
                                'if' => '${{github.ref == \'refs/heads/master\'}}',
                                'name' => 'Login to ghcr.io',
                                'uses' => 'docker/login-action@v3',
                                'with' => [
                                    'registry' => 'ghcr.io',
                                    'username' => '${{ github.actor }}',
                                    'password' => '${{ secrets.GITHUB_TOKEN }}',
                                ],
                            ],
                            [
                                'name' => 'Push arch image',
                                'if' => '${{github.ref == \'refs/heads/master\'}}',
                                'run' => 'docker push "ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}"-' . $node['tag'],
                            ],
                            $hasChildren ? [
                                'name' => 'Export image (OCI layout)',
                                'if' => self::PR_ONLY_IF,
                                'uses' => 'docker/build-push-action@v6',
                                'with' => array_merge(
                                    [
                                        'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
                                        'platforms' => '${{ matrix.platform }}',
                                        'cache-from' => 'type=gha',
                                        'cache-to' => 'type=gha,mode=max',
                                        'build-args' => implode("\n", [
                                            'TARGETARCH=${{ matrix.arch }}',
                                        ]),
                                    ],
                                    // Must use the exact same build-contexts override as the
                                    // tested build above, otherwise this second Buildx invocation
                                    // silently re-resolves FROM/COPY --from references against the
                                    // published registry images and the exported artifact no
                                    // longer reflects the tested result.
                                    $hasImageDependencies ? ['build-contexts' => $this->buildContexts($imageDependencies)] : [],
                                    ['outputs' => 'type=oci,tar=false,name=' . self::PARENT_OCI_TAG . ',dest=' . $this->getCiImagePath($node['id'])],
                                ),
                            ] : null,
                            $hasChildren ? [
                                'name' => 'Upload image (OCI layout)',
                                'if' => self::PR_ONLY_IF,
                                'uses' => 'actions/upload-artifact@v4',
                                'with' => [
                                    'name' => $this->getCiImageArtifactName($node['id']),
                                    'path' => $this->getCiImagePath($node['id']),
                                    'retention-days' => 1,
                                    'compression-level' => 0,
                                    'if-no-files-found' => 'error',
                                ],
                            ] : null,
                        ],
                    ),
                ),
            ],
            $jobId . '_publish' => [
                'name' => $node['name'] . ' - Publish',
                'runs-on' => 'ubuntu-latest',
                'needs' => $jobId,
                'if' => '${{github.ref == \'refs/heads/master\'}}',
                'steps' => [
                    ['uses' => 'docker/setup-buildx-action@v3'],
                    [
                        'name' => 'Login to ghcr.io',
                        'uses' => 'docker/login-action@v3',
                        'with' => [
                            'registry' => 'ghcr.io',
                            'username' => '${{ github.actor }}',
                            'password' => '${{ secrets.GITHUB_TOKEN }}',
                        ],
                    ],
                    [
                        'name' => 'Login to hub.docker.com',
                        'uses' => 'docker/login-action@v3',
                        'with' => [
                            'username' => '${{ secrets.DOCKERHUB_USERNAME }}',
                            'password' => '${{ secrets.DOCKERHUB_TOKEN }}',
                        ],
                    ],
                    [
                        'name' => 'Create and push multi-arch manifest',
                        'run' =>
                        // we need the retry loop here because sometimes docker hub returns errors when pushing manifests (especially if pushed to the same image multiple times in a short time frame)
                            implode("\n", [
                                'set -euo pipefail',
                                'for i in 1 2 3 4 5 6 7 8 9 10; do',
                                '  ' . implode(" \\\n  ", [
                                    'docker buildx imagetools create',
                                    ...$pushTags,
                                    '"ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-amd64-' . $node['tag'] . '"',
                                    '"ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-arm64-' . $node['tag'] . '" && exit 0',
                                ]),
                                '  sleep $((i*i))',
                                'done',
                                'exit 1',
                            ]),
                    ],
                ],
            ],
        ];
    }

    public static function toJobId(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace('webdevops/', '', $name);
        $name = str_replace(['/', '.'], '-', $name);
        $name = str_replace(':', '_', $name);
        return $name;
    }

    /**
     * Common `docker/build-push-action` inputs shared by the local build step
     * and the additional per-node variants (parent-context build, OCI export).
     */
    private function buildPushWith(array $node): array
    {
        return [
            'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
            'platforms' => '${{ matrix.platform }}',
            'load' => true,
            'tags' => 'ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}-' . $node['tag'],
            'cache-from' => 'type=gha',
            'cache-to' => 'type=gha,mode=max',
            'build-args' => implode("\n", [
                'TARGETARCH=${{ matrix.arch }}',
            ]),
        ];
    }

    /**
     * All internal webdevops/* images this node needs a same-run OCI
     * artifact override for: its real FROM parent (if any) plus any images
     * referenced via `COPY --from=webdevops/...`. Keyed by the *literal*
     * reference text as written in the Dockerfile (the key a Buildx
     * `build-contexts` override must match exactly), valued by the resolved
     * image id that identifies the job/artifact actually producing it.
     *
     * `node['parent']` is NOT used here on its own: it may be a purely
     * synthetic scheduling dependency (e.g. on the Toolbox job for images
     * whose Dockerfile does not actually FROM/COPY an internal image), which
     * must never drive OCI artifact propagation.
     */
    private function imageDependencies(array $node): array
    {
        $dependencies = [];
        if (!empty($node['imageParent'])) {
            $literalRef = $node['imageParentRef'] ?: $node['imageParent'];
            $dependencies[$literalRef] = $node['imageParent'];
        }
        foreach ($node['imageDependencies'] ?? [] as $literalRef => $resolvedImage) {
            $dependencies[$literalRef] = $resolvedImage;
        }
        return $dependencies;
    }

    /**
     * One "Download parent image (OCI layout)" step per distinct image id
     * referenced in $imageDependencies, deduplicated so the same artifact is
     * never downloaded twice (e.g. if an image happens to be both the FROM
     * parent and a COPY --from target).
     *
     * @return array<int, array<string, mixed>>
     */
    private function downloadParentImageSteps(array $imageDependencies): array
    {
        $imageIds = array_unique(array_values($imageDependencies));
        return array_map(fn (string $imageId): array => [
            'name' => 'Download parent image (OCI layout): ' . $imageId,
            'if' => self::PR_ONLY_IF,
            'uses' => 'actions/download-artifact@v4.1.9',
            'with' => [
                'name' => $this->getCiImageArtifactName($imageId),
                'path' => $this->getCiImagePath($imageId),
            ],
        ], $imageIds);
    }

    /**
     * Deterministic, filesystem/artifact-safe name for the image identified
     * by $imageId (e.g. "webdevops/php:8.4"), unique per architecture so
     * amd64/arm64 artifacts can never collide or be cross-consumed.
     */
    private function getCiImageArtifactName(string $imageId): string
    {
        return 'docker-parent-' . GithubJobBuilder::toJobId($imageId) . '-${{ matrix.arch }}';
    }

    /**
     * Predictable, collision-free extraction/export directory for the OCI
     * layout of the image identified by $imageId.
     *
     * Intentionally a path relative to the job's working directory (the
     * checked-out repository) rather than an absolute `${{ runner.temp }}`
     * path: every job in this workflow runs inside a `container:`, and
     * `${{ runner.temp }}` is evaluated by the Actions runner against the
     * *host* filesystem, which is only bind-mounted into the container under
     * `/__w/_temp`, not under the literal host path. A relative path is
     * resolved consistently by every step (checkout, Buildx, up-/download-artifact)
     * against the same container working directory, avoiding that mismatch.
     */
    private function getCiImagePath(string $imageId): string
    {
        return '.ci-oci-image/' . GithubJobBuilder::toJobId($imageId);
    }

    /**
     * Buildx `build-contexts` value mapping every literal FROM/COPY --from
     * reference in $imageDependencies to the OCI layout downloaded from the
     * corresponding parent job's artifact, so BuildKit never needs to pull
     * any of those images from Docker Hub during a pull_request build. Must
     * be reused unchanged for every Buildx invocation of this node (tested
     * build and OCI export alike), otherwise a later invocation silently
     * resolves a dependency from the registry again.
     */
    private function buildContexts(array $imageDependencies): string
    {
        $lines = [];
        foreach ($imageDependencies as $literalRef => $imageId) {
            $lines[] = $literalRef . '=oci-layout://' . $this->getCiImagePath($imageId) . ':' . self::PARENT_OCI_TAG;
        }
        return implode("\n", $lines);
    }


    private function serverSpec(array $node): array
    {
        $specFile = sprintf('spec/docker/%s_spec.rb', $node['image']);
        if (!file_exists(__DIR__ . '/../../tests/serverspec/' . $specFile)) {
            return [];
        }

        $testDockerfile = 'Dockerfile_test';
        $specConfig = $node['serverspec'];
        $specConfig['DOCKERFILE'] = $testDockerfile;
        $encodedJsonConfig = base64_encode(json_encode($specConfig));
        $script = [
            'cd tests/serverspec',
            'echo "FROM ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}"-' . $node['tag'] . ' >> ' . $testDockerfile,
            'echo "COPY conf/ /" >> ' . $testDockerfile,
        ];
        $script[] = 'bundle install';
        $script[] = 'bash serverspec.sh ' . $specFile . ' ' . $node['id'] . ' ' . $encodedJsonConfig . '  ' . $testDockerfile;
        return $script;
    }

    private function structuredTests(array $node): array
    {
        $script = [];
        if (file_exists(__DIR__ . '/../../tests/structure-test/' . $node['image'] . '/test.yaml')) {
            $script[] = 'cd tests/structure-test';
            if (file_exists(__DIR__ . '/../../tests/structure-test/' . $node['image'] . '/' . $node['tag'] . '/test.yaml')) {
                $script[] = '/usr/local/bin/container-structure-test test --image ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}-' . $node['tag'] . ' --config ' . $node['image'] . '/test.yaml --config ' . $node['image'] . '/' . $node['tag'] . '/test.yaml';
            } else {
                $script[] = '/usr/local/bin/container-structure-test test --image ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}-' . $node['tag'] . ' --config ' . $node['image'] . '/test.yaml';
            }
        }
        return $script;
    }

    public function getValidationConfig(): array
    {
        return [
            'name' => 'Validate Automation',
            'runs-on' => 'ubuntu-latest',
            'steps' => [
                ['uses' => 'actions/checkout@v6'],
                [
                    'name' => 'Validate that template/* are used to generate Dockerfiles',
                    'run' => implode("\n", [
                        'docker run --rm -v $PWD:/app -w /app webdevops/dockerfile-build-env make provision',
                        'git diff --exit-code --color=always',
                    ]),
                ],
                [
                    'name' => 'Validate .github/workflows/build.yaml is up to date',
                    'run' => implode("\n", [
                        'docker run --rm -v $PWD:/app -w /app/ci webdevops/php:8.4-alpine composer install',
                        'docker run --rm -v $PWD:/app -w /app webdevops/php:8.4-alpine ci/console github:generate-ci',
                        'git diff --exit-code --color=always',
                    ]),
                ],
            ],
        ];
    }
}
