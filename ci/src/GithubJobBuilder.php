<?php

namespace Webdevops\Build;

use function str_replace;

class GithubJobBuilder
{

    public function getJobDescription(array $node): array
    {
        $serverSpec = $this->serverSpec($node);
        $structuredTests = $this->structuredTests($node);
        $job = [];
        if ($node['parent'] ?? false) {
            // put needs at the beginning
            $job['needs'] = [GithubJobBuilder::toJobId($node['parent'])];
        }
        $job = [
            'name' => $node['name'],
            ...$job,
            'runs-on' => 'ubuntu-latest',
            'container' => 'webdevops/dockerfile-build-env',
            'steps' => array_values(
                array_filter(
                    [
                        ['uses' => 'actions/checkout@v4'],
                        ['uses' => 'docker/setup-qemu-action@v3'],
                        ['uses' => 'docker/setup-buildx-action@v3'],
                        [
                            'if' => "github.ref == 'refs/heads/master'",
                            'name' => 'Login to ghcr.io',
                            'uses' => 'docker/login-action@v3',
                            'with' => [
                                'registry' => 'ghcr.io',
                                'username' => '${{ github.actor }}',
                                'password' => '${{ secrets.GITHUB_TOKEN }}',
                            ],
                        ],
                        [
                            'if' => "github.ref == 'refs/heads/master'",
                            'name' => 'Login to hub.docker.com',
                            'uses' => 'docker/login-action@v3',
                            'with' => [
                                'username' => '${{ secrets.DOCKERHUB_USERNAME }}',
                                'password' => '${{ secrets.DOCKERHUB_TOKEN }}',
                            ],
                        ],
                        [
                            'name' => 'Build x64',
                            'uses' => 'docker/build-push-action@v6',
                            'with' => [
                                'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
                                'load' => true,
                                'tags' => 'ghcr.io/webdevops/' . $node['image'] . ':' . $node['tag'] . ',webdevops/' . $node['image'] . ':' . $node['tag'],
                                'platforms' => 'linux/amd64',
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
                            'if' => "github.ref == 'refs/heads/master'",
                            'name' => 'Push',
//                            'name' => 'Build ARM + Push',
                            'uses' => 'docker/build-push-action@v6',
                            'with' => [
                                'context' => dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
                                'push' => true,
                                'tags' => 'ghcr.io/webdevops/' . $node['image'] . ':' . $node['tag'] . ',webdevops/' . $node['image'] . ':' . $node['tag'],
                                'platforms' => 'linux/amd64',
//                                'platforms' => 'linux/amd64,linux/arm64', // ARM not ready yet
                            ],
                        ],
                    ],
                ),
            ),
        ];
        return $job;
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

//        $testDockerfile = uniqid('Dockerfile_', true);
        $testDockerfile = 'Dockerfile_test';
        $specConfig = $node['serverspec'];
        $specConfig['DOCKERFILE'] = $testDockerfile;
        $encodedJsonConfig = base64_encode(json_encode($specConfig));
        $script = [
            'cd tests/serverspec',
            'echo "FROM ' . $node['id'] . '" >> ' . $testDockerfile,
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
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $node['name'] . ' --config ' . $node['image'] . '/test.yaml --config ' . $node['image'] . '/' . $node['tag'] . '/test.yaml';
            } else {
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $node['name'] . ' --config ' . $node['image'] . '/test.yaml';
            }
        }
        return $script;
    }

}
