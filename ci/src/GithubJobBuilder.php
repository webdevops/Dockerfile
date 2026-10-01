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
     * @return array<string, array<string, mixed>>
     */
    public function getJobsDescription(array $node): array
    {
        $serverSpec = $this->serverSpec($node);
        $structuredTests = $this->structuredTests($node);

        $jobId = GithubJobBuilder::toJobId($node['name']);
        $hasParent = $this->hasInternalParent($node);
        $hasChildren = !empty($node['hasChildren']);
        $parentJobId = $hasParent ? GithubJobBuilder::toJobId($node['parent']) : null;
        $needs = $hasParent ? [$parentJobId, $parentJobId . '_publish'] : 'validate-automation';

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
                            $hasParent ? [
                                'name' => 'Download parent image (OCI layout)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'actions/download-artifact@v4.1.9',
                                'with' => [
                                    'name' => $this->getCiImageArtifactName($node['parent']),
                                    'path' => $this->getCiImagePath($node['parent']),
                                ],
                            ] : null,
                            array_filter([
                                'name' => 'Build (load locally)',
                                'if' => $hasParent ? '${{ github.ref == \'refs/heads/master\' }}' : null,
                                'uses' => 'docker/build-push-action@v6',
                                'with' => $this->buildPushWith($node),
                            ]),
                            $hasParent ? [
                                'name' => 'Build (load locally, from parent artifact)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'docker/build-push-action@v6',
                                'with' => array_merge(
                                    $this->buildPushWith($node),
                                    ['build-contexts' => $this->parentBuildContext($node)],
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
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'docker/build-push-action@v6',
                                'with' => [
                                    'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
                                    'platforms' => '${{ matrix.platform }}',
                                    'cache-from' => 'type=gha',
                                    'cache-to' => 'type=gha,mode=max',
                                    'build-args' => implode("\n", [
                                        'TARGETARCH=${{ matrix.arch }}',
                                    ]),
                                    'outputs' => 'type=oci,tar=false,name=' . self::PARENT_OCI_TAG . ',dest=' . $this->getCiImagePath($node['id']),
                                ],
                            ] : null,
                            $hasChildren ? [
                                'name' => 'Upload image (OCI layout)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
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
     * Whether the node's `FROM` statement points to another internal
     * webdevops/* image built by this same workflow.
     */
    private function hasInternalParent(array $node): bool
    {
        return (bool)($node['parent'] ?? null);
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
     */
    private function getCiImagePath(string $imageId): string
    {
        return '${{ runner.temp }}/ci-oci-image/' . GithubJobBuilder::toJobId($imageId);
    }

    /**
     * Buildx `build-contexts` entry that maps the exact parent reference used
     * in the Dockerfile's `FROM` statement to the OCI layout downloaded from
     * the parent job's artifact, so BuildKit never needs to pull the parent
     * image from Docker Hub during non-master builds.
     */
    private function parentBuildContext(array $node): string
    {
        return $node['parent'] . '=oci-layout://' . $this->getCiImagePath($node['parent']) . ':' . self::PARENT_OCI_TAG;
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
