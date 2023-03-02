<?php

namespace Webdevops\Build;

class JobBuilder
{

    public function getJobDescription(array $node)
    {
        $jobDefinition = $this->getBasicJobDefinition($node);
        $jobDefinition['script'] = $this->buildScript($node);
        return $jobDefinition;
    }

    private function getBasicJobDefinition(array $node)
    {
        $job = [
            'stage' => 'level' . $node['level'],
            'before_script' => [
                'docker login -u $DOCKER_USER -p $DOCKER_PASS',
                'docker login -u $CI_REGISTRY_USER -p $CI_JOB_TOKEN $CI_REGISTRY',
            ],
            'image' => 'webdevops/dockerfile-build-env',
            'script' => [],
//            'retry' => 2,
            'tags' => ['aws'],
//             'only' => ['master'],
        ];
        if ($node['parent'] !== 0) {
            $job['needs'] = [$node['parent']];
        }
        return $job;
    }

    private function buildScript(array $node)
    {
        $script = $this->buildImage($node);
        $script = array_merge($script, $this->serverSpec($node));
        $script = array_merge($script, $this->structuredTests($node));
        $script = array_merge($script, $this->pushImage($node));
        return $script;
    }

    private function buildImage(array $node)
    {
        return [
            'cd ' . dirname(str_replace(__DIR__ . '/../../', '', $node['file'])),
            'docker build --no-cache -t webdevops/' . $node['image'] . ':' . $node['tag'] . ' .',
        ];
    }

    private function pushImage(array $node)
    {
        $script[] = 'docker push ' . $node['id'];
        $script[] = 'docker tag ' . $node['id'] . ' $CI_REGISTRY_IMAGE/' . $node['image'] . ':' . $node['tag'];
        $script[] = 'docker push $CI_REGISTRY_IMAGE/' . $node['image'] . ':' . $node['tag'];
        foreach ($node['aliases'] as $alias) {
            $script[] = 'docker tag ' . $node['id'] . ' ' . $alias;
            $script[] = 'docker push ' . $alias;
        }
        return $script;
    }

    private function serverSpec(array $node)
    {
        $specFile = sprintf('spec/docker/%s_spec.rb', $node['image']);
        if (!file_exists(__DIR__ . '/../../tests/serverspec/' . $specFile)) {
            return [];
        }

        $testDockerfile = uniqid('Dockerfile_', true);
        $specConfig = $node['serverspec'];
        $specConfig['DOCKERFILE'] = $testDockerfile;
        $encodedJsonConfig = base64_encode(json_encode($specConfig));
        $script = [
            'cd $CI_PROJECT_DIR/tests/serverspec',
            'echo "FROM ' . $node['id'] . '" >> ' . $testDockerfile,
            'echo "COPY conf/ /" >> ' . $testDockerfile,
        ];
        $script[] = 'bundle install';
        $script[] = 'bash serverspec.sh ' . $specFile . ' ' . $node['id'] .' ' . $encodedJsonConfig  . '  ' . $testDockerfile;
        return $script;
    }

    private function structuredTests(array $node)
    {
        $script = [];
        if (file_exists(__DIR__ . '/../../tests/structure-test/' . $node['image'] . '/test.yaml')) {
            $script[] = 'cd $CI_PROJECT_DIR/tests/structure-test';
            if (file_exists(__DIR__ . '/../../tests/structure-test/' . $node['image'] . '/' . $node['tag'] . '/test.yaml')) {
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $node['name'] . ' --config ' . $node['image'] . '/test.yaml --config ' . $node['image'] . '/' . $node['tag'] . '/test.yaml';
            } else {
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $node['name'] . ' --config ' . $node['image'] . '/test.yaml';
            }
        }
        return $script;
    }

}
