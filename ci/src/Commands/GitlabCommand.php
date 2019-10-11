<?php

namespace Webdevops\Build\Commands;

use BlueM\Tree;
use BlueM\Tree\Node;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    protected static $defaultName = 'gitlab:generate-ci';

    public function __construct()
    {
        $this->fileReader = new FileReader();
        $this->jobBuilder = new JobBuilder();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $tree = $this->buildTree();
        foreach ($tree->getRootNodes() as $rootNode) {
            $this->traverse($rootNode);
        }
        $gitlabCi['stages'] = array_map(function($level) {return 'level' . $level;}, range(1, $this->deepestLevel));
        $yaml = Yaml::dump(array_merge($gitlabCi, $this->jobs), 3, 2);
        file_put_contents('.gitlab-ci.yml', $yaml);
    }

    private function traverse(Node $node)
    {
        $line = 'Processing ' . $node->getName();
        $this->output->write([str_pad('', $node->getLevel() - 1, "\t", STR_PAD_LEFT), $line, PHP_EOL]);
        $nodeAr = $node->toArray();
        $nodeAr['level'] = $node->getLevel();
        if ($node->getLevel() > $this->deepestLevel) {
            $this->deepestLevel = $node->getLevel();
        }
        $this->jobs[$node->getId()] = $this->jobBuilder->getJobDescription($nodeAr);
        foreach ($node->getChildren() as $childNode) {
            $this->traverse($childNode);
        }
    }

    private function buildTree()
    {
        $data = [];
        $dockerFiles = $this->fileReader->collectDockerfiles();
        foreach ($dockerFiles as $file) {
            $data[] = $this->fileReader->getInfo($file[0]);
        }
        // Add latest tagged images
        foreach ($data as $datum) {
            if (strpos($datum['name'], 'ubuntu-18.04') !== false) {
                $clone = $datum;
                $clone['id'] = $clone['name'] = str_replace(':ubuntu-18.04', ':latest', $clone['id']);
                $data[] = $clone;
            }
        }
        return new Tree($data);
    }

}
