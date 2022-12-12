<?php

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

class DockerfileStruct {
    public $path = '';
    public $image = '';
    public $jobName = '';
    public $dependency = null;
    public $dependencyIsExternal = true;
    public $level = 0;

    public function parse()
    {
        $this->checkDependency();
        $this->getImageName();
        $this->generateJobName();
    }

    private function checkDependency()
    {
        $f = fopen($this->path, 'r');
        while (($line = fgets($f)) !== false) {
            if (strpos($line, 'FROM') === 0) {
                $this->dependency = trim(substr($line, 5));
                if (strpos($this->dependency, 'webdevops/') === 0) {
                    $this->dependencyIsExternal = false;
                    $this->dependency = str_replace('latest', 'ubuntu-16.04', $this->dependency);
                } else {
                    $this->dependencyIsExternal = true;
                }
            }
        }
        fclose($f);
    }

    private function getImageName()
    {
        $f = fopen($this->path, 'r');
        while (($line = fgets($f)) !== false) {
            if (strpos($line, '# Dockerfile for ') === 0) {
                $imageName = trim(substr($line, 17));
                $imageName = str_replace('-official', '', $imageName);
                $this->image = $imageName;
                break;
            }
        }
        fclose($f);
    }

    private function generateJobName()
    {
        $jobName = str_replace('webdevops/', '', $this->image);
        $this->jobName = $jobName;
    }
}


$gitlabCi = [
    'image' => 'docker',
    'stages' => [],
];
$dockerfiles = [];
$maxLevel = 0;

/*
 * Scan for all Dockerfile files
 */
chdir(__DIR__ . '/../');
$dirItr    = new RecursiveDirectoryIterator('docker');
$itr       = new RecursiveIteratorIterator($dirItr);
foreach ($itr as $filePath => $fileInfo) {
    if ($fileInfo->getFilename() === 'Dockerfile') {
        $dockerfile = new DockerfileStruct();
        $dockerfile->path = $fileInfo->getPathname();
        $dockerfile->parse();
        $dockerfiles[$dockerfile->image] = $dockerfile;
    }
}

/*
 * Build dependency levels
 */
$checkedImages = [];
while (count($checkedImages) < count($dockerfiles)) {
    foreach ($dockerfiles as $image => $dockerfile) {
        if (in_array($image, $checkedImages)) {
            continue;
        }
        if ($dockerfile->dependencyIsExternal) {
            $checkedImages[] = $image;
        } else if (in_array($dockerfile->dependency, $checkedImages)) {
            $dockerfile->level = $dockerfiles[$dockerfile->dependency]->level + 1;
            if ($dockerfile->level > $maxLevel) {
                $maxLevel = $dockerfile->level;
            }
            $checkedImages[] = $image;
            $dockerfiles[$image] = $dockerfile;
        }
    }
}

/*
 * Fill stages
 */
$gitlabCi['stages'] = array_map(function($i) { return 'level' . $i; }, range(0, $maxLevel));
array_unshift($gitlabCi['stages'], 'build-env');

/*
 * Sort dockerfiles by level and jobName
 */
usort($dockerfiles, function($a, $b) {
    if ($a->level === $b->level) {
        return ($a->jobName < $b->jobName) ? -1 : 1;
    }
    return ($a->level < $b->level) ? -1 : 1;
});

/*
 * Build Gitlab CI YAML
 */
foreach ($dockerfiles as $dockerfile) {
    $script = [
        'cd ' . dirname($dockerfile->path),
        //'docker build --no-cache -t ' . $dockerfile->image . ' .',
        'docker build --no-cache -t $CI_REGISTRY_IMAGE/' . $dockerfile->jobName . ' .',
    ];

    // Add tests if available
    list($type, $distro) = explode(':', $dockerfile->jobName);
    if (file_exists(__DIR__ . '/../tests/structure-test/' . $type . '/test.yaml')) {
        $script[] = 'cd $CI_PROJECT_DIR/tests/structure-test';
        if (file_exists(__DIR__ . '/../tests/structure-test/' . $type . '/' . $distro . '/test.yaml')) {
            //$script[] = 'container-structure-test test --image ' . $dockerfile->image . ' --config ' . $type . '/test.yaml --config ' . $type . '/' . $distro . '/test.yaml';
            $script[] = '/usr/local/bin/container-structure-test test --image $CI_REGISTRY_IMAGE/' . $dockerfile->jobName . ' --config ' . $type . '/test.yaml --config ' . $type . '/' . $distro . '/test.yaml';
        } else {
            //$script[] = 'container-structure-test test --image ' . $dockerfile->image . ' --config ' . $type . '/test.yaml';
            $script[] = '/usr/local/bin/container-structure-test test --image $CI_REGISTRY_IMAGE/' . $dockerfile->jobName . ' --config ' . $type . '/test.yaml';
        }
    }

    /*
    $testDockerfile = uniqid('Dockerfile_', true);
    if (true) {
        $script = array_merge($script, [
            'cd $CI_PROJECT_DIR/tests/serverspec',
            'echo "FROM ' . $dockerfile->image . '" >> ' . $testDockerfile,
            'echo "COPY conf/ /" >> ' . $testDockerfile,
            'bash serverspec.sh spec/docker/php_spec.rb ' . $dockerfile->image .' ' . $encodedJsonConfig  . '  ' . $testDockerfile,
        ]);
    }
    */
    $script = array_merge($script, [
        //'docker push ' . $dockerfile->image
        'docker push $CI_REGISTRY_IMAGE/' . $dockerfile->jobName
    ]);

    $gitlabCi[$dockerfile->jobName] = [
        'stage' => 'level' . $dockerfile->level,
        'before_script' => [
            //'docker login -u $DOCKER_USER -p $DOCKER_PASS'
            'apk add curl',
            'curl -LO https://storage.googleapis.com/container-structure-test/latest/container-structure-test-linux-amd64',
            'chmod +x container-structure-test-linux-amd64',
            'mv container-structure-test-linux-amd64 /usr/local/bin/container-structure-test',
            'docker login -u $CI_REGISTRY_USER -p $CI_JOB_TOKEN $CI_REGISTRY'
        ],
        'script' => $script,
        'retry' => 2,
        'tags' => ['aws'],
        //'only' => ['master']
    ];
    if (!$dockerfile->dependencyIsExternal && !empty($dockerfile->dependency)) {
        $gitlabCi[$dockerfile->jobName]['dependencies'] = [str_replace('webdevops/', '', $dockerfile->dependency)];
    }
}
// TODO: fix cyclic dependency
//$gitlabCi['dockerfile-build-env:latest']['stage'] = 'build-env';


/*
 * Store YAML
 */
$yaml = Yaml::dump($gitlabCi, 4);
file_put_contents('ci/gitlab-ci.yml', $yaml);
