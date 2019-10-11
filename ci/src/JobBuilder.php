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
            'script' => [],
            'retry' => 2,
            'tags' => ['aws'],
            'only' => ['master'],
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
            'cd ' . dirname($node['file']),
            'docker build --no-cache -t $CI_REGISTRY_IMAGE/' . $node['image'] . ':' . $node['tag'] . ' .',
        ];
    }

    private function pushImage(array $node)
    {
        $script[] = 'docker tag $CI_REGISTRY_IMAGE/' . $node['image'] . ':' . $node['tag'] . ' ' . $node['id'];
        $script[] = 'docker push ' . $node['id'];
        foreach ($node['aliases'] as $alias) {
            $script[] = 'docker tag $CI_REGISTRY_IMAGE/' . $node['image'] . ':' . $node['tag'] . ' ' . $alias;
            $script[] = 'docker push ' . $alias;
        }
        return $script;
    }

    private function serverSpec(array $node)
    {
        $testDockerfile = uniqid('Dockerfile_', true);
        $specConfig = [
            'DOCKERFILE' => $testDockerfile,
            'DOCKER_IMAGE' => $node['id'],
            'DOCKER_IS_TOOLIMAGE' => '0',
            'DOCKER_TAG' => $node['tag'],
            'OS_FAMILY' => $node['os'],
            'OS_VERSION' => $node['os-version'],
        ];
        $encodedJsonConfig = base64_encode(json_encode($specConfig));
        $script = [
            'cd $CI_PROJECT_DIR/tests/serverspec',
            'echo "FROM ' . $node['id'] . '" >> ' . $testDockerfile,
            'echo "COPY conf/ /" >> ' . $testDockerfile,
            'bash serverspec.sh spec/docker/php_spec.rb ' . $node['id'] .' ' . $encodedJsonConfig  . '  ' . $testDockerfile,
        ];
        return $script;
    }

    private function structuredTests(array $node)
    {
        $script = [];
        /*if (file_exists(__DIR__ . '/../tests/structure-test/' . $node['image'] . '/test.yaml')) {
            $script[] = 'cd $CI_PROJECT_DIR/tests/structure-test';
            if (file_exists(__DIR__ . '/../tests/structure-test/' . $node['image'] . '/' . $distro . '/test.yaml')) {
                //$script[] = 'container-structure-test test --image ' . $dockerfile->image . ' --config ' . $type . '/test.yaml --config ' . $type . '/' . $distro . '/test.yaml';
                $script[] = '/usr/local/bin/container-structure-test test --image $CI_REGISTRY_IMAGE/' . $dockerfile->jobName . ' --config ' . $type . '/test.yaml --config ' . $type . '/' . $distro . '/test.yaml';
            } else {
                //$script[] = 'container-structure-test test --image ' . $dockerfile->image . ' --config ' . $type . '/test.yaml';
                $script[] = '/usr/local/bin/container-structure-test test --image $CI_REGISTRY_IMAGE/' . $dockerfile->jobName . ' --config ' . $type . '/test.yaml';
            }
        }*/
        return $script;
    }

}
