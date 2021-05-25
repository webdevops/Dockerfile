<?php

namespace Webdevops\Build\Commands;

use BlueM\Tree;
use BlueM\Tree\Node;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\FileReader;
use Webdevops\Build\JobBuilder;

class GitlabCommand extends Command
{
    protected $output;
    protected $fileReader;
    protected $jobBuilder;
    protected $jobs = [];
    protected $deepestLevel = 0;
    protected $blacklist = [];
    protected $_settings = [];

    protected static $defaultName = 'gitlab:generate-ci';

    public function __construct()
    {
        $this->fileReader = new FileReader();
        $this->jobBuilder = new JobBuilder();
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
        $gitlabCi['stages'] = array_map(function($level) {return 'level' . $level;}, range(1, $this->deepestLevel));
        $yaml = Yaml::dump(array_merge($gitlabCi, $this->jobs), 3, 2);
        file_put_contents(__DIR__ . '/../../../.gitlab-ci.yml', $yaml);
        return 0;
    }

    private function traverse(Node $node)
    {
        $line = 'Processing ' . $node->getName();
        $nodeAr = $node->toArray();
        $nodeAr['level'] = $node->getLevel();
        if ($node->getLevel() > $this->deepestLevel) {
            $this->deepestLevel = $node->getLevel();
        }
        $this->jobs[$node->getId()] = $this->jobBuilder->getJobDescription($nodeAr);
        if ($this->isNameBlacklisted($nodeAr['id'])) {
//            $this->jobs[$node->getId()] = array_merge($this->jobs[$node->getId()], ['when' => 'manual']);
            $line .= ' *blacklisted*';
            if ($node->get('tag') !== $this->_settings['docker']['autoLatestTag']) {
                unset($this->jobs[$node->getId()]);
            }
        }
        $this->output->write([str_pad('', $node->getLevel() - 1, "\t", STR_PAD_LEFT), $line, PHP_EOL]);
        foreach ($node->getChildren() as $childNode) {
            $this->traverse($childNode);
        }
    }

    private function isNameBlacklisted(string $name)
    {
        foreach ($this->blacklist as $blacklistItem) {
            if (strpos($name, $blacklistItem)) {
                return true;
            }
        }
        return false;
    }

    private function buildTree()
    {
        $data = [];
        $dockerFiles = $this->fileReader->collectDockerfiles();
        foreach ($dockerFiles as $file) {
            $data[] = $this->fileReader->getInfo($file[0]);
        }
        return new Tree($data);
    }

}
