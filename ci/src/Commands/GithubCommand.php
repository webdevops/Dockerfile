<?php

namespace Webdevops\Build\Commands;

use BlueM\Tree;
use BlueM\Tree\Node;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\FileReader;
use Webdevops\Build\GithubJobBuilder;

use function array_filter;
use function array_map;
use function array_values;
use function file;
use function file_exists;
use function file_put_contents;
use function is_string;
use function ksort;
use function str_contains;
use function str_pad;
use function trim;

/**
 * Writes .github/workflows/build.yaml: a build and a publish job for every Dockerfile under docker/.
 */
#[AsCommand(name: 'github:generate-ci')]
class GithubCommand extends Command
{
    private OutputInterface $output;

    private readonly FileReader $fileReader;

    private readonly GithubJobBuilder $jobBuilder;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $jobs = [];

    /**
     * @var list<string>
     */
    private array $blacklist = [];

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../../..')
    {
        $this->fileReader = new FileReader($this->rootPath);
        $this->jobBuilder = new GithubJobBuilder($this->rootPath);
        parent::__construct();
        $this->addOption('blacklist', 'b', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->blacklist = $this->blacklistEntries($input->getOption('blacklist'));
        if ($this->blacklist === [] && file_exists($this->rootPath . '/ci/BLACKLIST')) {
            $this->blacklist = $this->blacklistEntries(file($this->rootPath . '/ci/BLACKLIST', FILE_IGNORE_NEW_LINES) ?: []);
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
            $line .= ' *blacklisted*';
            if ($node->get('tag') !== $this->fileReader->autoLatestTag()) {
                unset($this->jobs[GithubJobBuilder::toJobId($node->getId())]);
                unset($this->jobs[GithubJobBuilder::toJobId($node->getId()) . '_publish']);
            }
        }

        $this->output->write([str_pad('', $node->getLevel() - 1, "\t", STR_PAD_LEFT), $line, PHP_EOL]);
        foreach ($node->getChildren() as $childNode) {
            $this->traverse($childNode);
        }
    }

    /**
     * The entries of --blacklist or ci/BLACKLIST without surrounding whitespace, which no image name contains,
     * and without empty entries, which would match every image.
     *
     * @param array<mixed> $entries
     * @return list<string>
     */
    private function blacklistEntries(array $entries): array
    {
        $entries = array_map(fn (mixed $entry): string => is_string($entry) ? trim($entry) : '', $entries);
        return array_values(array_filter($entries, fn (string $entry): bool => $entry !== ''));
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
