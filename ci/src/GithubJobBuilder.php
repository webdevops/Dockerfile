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
     * @return array<string, array<string, mixed>>
     */
    public function getJobsDescription(array $node): array
    {
        $serverSpec = $this->serverSpec($node);
        $structuredTests = $this->structuredTests($node);

        $jobId = GithubJobBuilder::toJobId($node['name']);
        $needs = ($node['parent'] ?? null) ? GithubJobBuilder::toJobId($node['parent']) . '_publish' : 'validate-automation';

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
                            [
                                'name' => 'Build (load locally)',
                                'uses' => 'docker/build-push-action@v6',
                                'with' => [
                                    'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
                                    'platforms' => '${{ matrix.platform }}',
                                    'load' => true,
                                    'tags' => 'ghcr.io/webdevops/' . $node['image'] . ':sha-${{ github.sha }}-${{ matrix.arch }}-' . $node['tag'],
                                    'cache-from' => 'type=gha',
                                    'cache-to' => 'type=gha,mode=max',
                                    'build-args' => implode("\n", [
                                        'TARGETARCH=${{ matrix.arch }}',
                                    ]),
                                ],
                            ],
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
