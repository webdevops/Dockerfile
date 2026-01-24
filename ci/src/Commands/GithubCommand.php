<?php

namespace Webdevops\Build\Commands;

use BlueM\Tree;
use BlueM\Tree\Node;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\FileReader;
use Webdevops\Build\GithubJobBuilder;

use function ksort;

class GithubCommand extends Command
{
    protected $output;
    protected $fileReader;
    protected $jobBuilder;
    protected $jobs = [];
    protected $deepestLevel = 0;
    protected $blacklist = [];
    protected $_settings = [];

    protected static $defaultName = 'github:generate-ci';

    public function __construct()
    {
        $this->fileReader = new FileReader();
        $this->jobBuilder = new GithubJobBuilder();
        parent::__construct();
        $this->addOption('blacklist', 'b', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
        $this->_settings = Yaml::parseFile(__DIR__ . '/../../../conf/console.yml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->blacklist = $input->getOption('blacklist');
        if (empty($this->blacklist) && file_exists(__DIR__ . '/../../BLACKLIST')) {
            $this->blacklist = file(__DIR__ . '/../../BLACKLIST', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        $this->output = $output;
        $tree = $this->buildTree();
        foreach ($tree->getRootNodes() as $rootNode) {
            $this->traverse($rootNode);
        }

        ksort($this->jobs); // system independent order of jobs

        $this->jobs = [
            'validate-automation' => $this->jobBuilder->getValidationConfig(),
            ...$this->jobs,
        ];;
        $buildYaml = [
            'name' => 'build',
            'on' => [
                'schedule' => [
                    ['cron' => '0 0 * * 2'], // every week on Tuesday
                ],
                'push' => null,
                'pull_request' => [
                    'branches' => ['master'],
                ],
                'workflow_dispatch' => null,
            ],
            'jobs' => $this->jobs,
        ];
        $yamlString = Yaml::dump(
            $buildYaml,
            99,
            2,
            Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK,
        );
        file_put_contents(__DIR__ . '/../../../.github/workflows/build.yaml', $yamlString);
        return 0;
    }

    private function traverse(Node $node): void
    {
        $line = 'Processing ' . $node->getName();
        $nodeAr = $node->toArray();
        $nodeAr['level'] = $node->getLevel();
        if ($node->getLevel() > $this->deepestLevel) {
            $this->deepestLevel = $node->getLevel();
        }
        $this->jobs[GithubJobBuilder::toJobId($node->getId())] = $this->jobBuilder->getJobDescription($nodeAr);
        if ($this->isNameBlacklisted($nodeAr['id'])) {
//            $this->jobs[GithubJobBuilder::toJobId($node->getId())] = array_merge($this->jobs[GithubJobBuilder::toJobId($node->getId())], ['when' => 'manual']);
            $line .= ' *blacklisted*';
            if ($node->get('tag') !== $this->_settings['docker']['autoLatestTag']) {
                unset($this->jobs[GithubJobBuilder::toJobId($node->getId())]);
            }
        }
        $this->output->write([str_pad('', $node->getLevel() - 1, "\t", STR_PAD_LEFT), $line, PHP_EOL]);
        foreach ($node->getChildren() as $childNode) {
            $this->traverse($childNode);
        }
    }

    private function isNameBlacklisted(string $name): bool
    {
        foreach ($this->blacklist as $blacklistItem) {
            if (strpos($name, $blacklistItem)) {
                return true;
            }
        }
        return false;
    }

    private function buildTree(): Tree
    {
        $data = [];
        $dockerFiles = $this->fileReader->collectDockerfiles();
        foreach ($dockerFiles as $file) {
            $data[] = $this->fileReader->getInfo($file[0]);
        }
        return new Tree($data);
    }
}
