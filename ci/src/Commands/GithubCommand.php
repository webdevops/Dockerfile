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

use function file;
use function file_exists;
use function file_put_contents;
use function ksort;
use function str_pad;
use function str_contains;

/**
 * Writes .github/workflows/build.yaml: a build and a publish job for every Dockerfile under docker/.
 */
class GithubCommand extends Command
{
    protected static $defaultName = 'github:generate-ci';

    private OutputInterface $output;

    private readonly FileReader $fileReader;

    private readonly GithubJobBuilder $jobBuilder;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $jobs = [];

    /**
     * @var array<string>
     */
    private array $blacklist = [];

    /**
     * @var array<string, mixed> conf/console.yml
     */
    private readonly array $settings;

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../../..')
    {
        $this->fileReader = new FileReader($this->rootPath);
        $this->jobBuilder = new GithubJobBuilder($this->rootPath);
        parent::__construct();
        $this->addOption('blacklist', 'b', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
        $this->settings = Yaml::parseFile($this->rootPath . '/conf/console.yml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->blacklist = $input->getOption('blacklist');
        if ($this->blacklist === [] && file_exists($this->rootPath . '/ci/BLACKLIST')) {
            $this->blacklist = file($this->rootPath . '/ci/BLACKLIST', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }

        $this->jobs = [];
        $this->output = $output;
        $tree = $this->buildTree();
        foreach ($tree->getRootNodes() as $rootNode) {
            $this->traverse($rootNode);
        }

        ksort($this->jobs); // system independent order of jobs

        $this->jobs = [
            'validate-automation' => $this->jobBuilder->getValidationConfig(),
            'check-ci-code' => $this->jobBuilder->getCodeCheckConfig(),
            ...$this->jobs,
        ];
        $buildYaml = [
            'name' => 'build',
            'on' => [
                'schedule' => [
                    ['cron' => '0 0 * * *'], // every week on Tuesday
                ],
                // Restricted to 'master' so a PR branch's commits are only
                // ever built once, via the 'pull_request' event below. A
                // broad `push: null` trigger (any branch) would otherwise
                // fire a second, fully duplicate run of the whole pipeline
                // (build, test, and the OCI artifact dance) for the exact
                // same commit as its corresponding pull_request run. Master
                // itself is never reached by 'pull_request' (it has no PR
                // targeting itself), so its push-triggered publish path is
                // unaffected.
                'push' => [
                    'branches' => ['master'],
                ],
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
        file_put_contents($this->rootPath . '/.github/workflows/build.yaml', $yamlString);
        return Command::SUCCESS;
    }

    private function traverse(Node $node): void
    {
        $line = 'Processing ' . $node->get('name');
        $nodeAr = $node->toArray();
        $nodeAr['level'] = $node->getLevel();
        $nodeAr['hasChildren'] = $node->hasChildren();
        $this->jobs = [
            ...$this->jobs,
            ...$this->jobBuilder->getJobsDescription($nodeAr),
        ];
        if ($this->isNameBlacklisted($nodeAr['id'])) {
//            $this->jobs[GithubJobBuilder::toJobId($node->getId())] = array_merge($this->jobs[GithubJobBuilder::toJobId($node->getId())], ['when' => 'manual']);
            $line .= ' *blacklisted*';
            if ($node->get('tag') !== $this->settings['docker']['autoLatestTag']) {
                unset($this->jobs[GithubJobBuilder::toJobId($node->getId())]);
                unset($this->jobs[GithubJobBuilder::toJobId($node->getId()) . '_publish']);
            }
        }

        $this->output->write([str_pad('', $node->getLevel() - 1, "\t", STR_PAD_LEFT), $line, PHP_EOL]);
        foreach ($node->getChildren() as $childNode) {
            $this->traverse($childNode);
        }
    }

    private function isNameBlacklisted(string $name): bool
    {
        return array_any($this->blacklist, fn (string $blacklistItem): bool => str_contains($name, $blacklistItem));
    }

    private function buildTree(): Tree
    {
        $data = [];
        foreach ($this->fileReader->collectDockerfiles() as $dockerfile) {
            $data[] = $this->fileReader->getInfo($dockerfile);
        }

        return new Tree($data);
    }
}
