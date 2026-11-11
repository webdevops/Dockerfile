<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use Webdevops\Build\Commands\GenerateGithubCiCommand;
use Webdevops\Build\Tests\TemporaryRepository;

use function array_column;
use function array_diff;
use function array_keys;
use function array_values;
use function strpos;
use function substr;

#[CoversClass(GenerateGithubCiCommand::class)]
final class GenerateGithubCiCommandTest extends TestCase
{
    private const array ALL_JOBS = [
        'validate-automation',
        'check-ci-code',
        'base-app_ubuntu-22-04',
        'base-app_ubuntu-22-04_publish',
        'base_alpine',
        'base_alpine_publish',
        'base_ubuntu-22-04',
        'base_ubuntu-22-04_publish',
        'php-nginx-dev_8-5-alpine',
        'php-nginx-dev_8-5-alpine_publish',
        'php-nginx_8-5-alpine',
        'php-nginx_8-5-alpine_publish',
        'php_8-4',
        'php_8-4_publish',
        'php_8-5',
        'php_8-5-alpine',
        'php_8-5-alpine_publish',
        'php_8-5_publish',
        'toolbox_latest',
        'toolbox_latest_publish',
    ];

    private TemporaryRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TemporaryRepository();
        $this->repository->write('.github/workflows/build.yaml', '');
    }

    protected function tearDown(): void
    {
        $this->repository->remove();
    }

    public function testWritesTheWorkflowWithTheValidationFirstAndTheImageJobsSorted(): void
    {
        $workflow = $this->generate();

        self::assertSame('build', $workflow['name']);
        self::assertSame([
            'schedule' => [['cron' => '0 0 * * *']],
            'push' => ['branches' => ['master']],
            'pull_request' => ['branches' => ['master']],
            'workflow_dispatch' => null,
        ], $workflow['on']);
        self::assertSame(self::ALL_JOBS, array_keys($workflow['jobs']));
        self::assertSame('Validate Automation', $workflow['jobs']['validate-automation']['name']);
    }

    public function testJobsFollowTheImageTree(): void
    {
        $jobs = $this->generate()['jobs'];

        self::assertSame('validate-automation', $jobs['toolbox_latest']['needs']);
        self::assertSame('toolbox_latest_publish', $jobs['base_alpine']['needs']);
        self::assertSame('toolbox_latest_publish', $jobs['php_8-4']['needs']);
        self::assertSame('base_ubuntu-22-04_publish', $jobs['base-app_ubuntu-22-04']['needs']);
        self::assertSame('php_8-5-alpine_publish', $jobs['php-nginx_8-5-alpine']['needs']);
        self::assertSame('php-nginx_8-5-alpine_publish', $jobs['php-nginx-dev_8-5-alpine']['needs']);
    }

    public function testParentImagesArePassedOnBetweenJobs(): void
    {
        $jobs = $this->generate()['jobs'];
        $steps = fn (string $job): array => array_column($jobs[$job]['steps'], null, 'name');

        // Lower case keys of the build tree are turned back into imageParent and imageParentRef
        self::assertSame(
            'webdevops/php:8.5-alpine=oci-layout://.ci-oci-image/php_8-5-alpine:ci-parent-image',
            $steps('php-nginx_8-5-alpine')['Build (load locally, from parent artifact)']['with']['build-contexts'],
        );
        self::assertArrayHasKey('Export image (OCI layout)', $steps('php_8-5-alpine'));
        self::assertArrayHasKey('Export image (OCI layout)', $steps('toolbox_latest'));
        self::assertArrayNotHasKey('Export image (OCI layout)', $steps('php_8-4'));
    }

    public function testPublishesTheAliases(): void
    {
        $jobs = $this->generate()['jobs'];
        $manifest = fn (string $job): string => array_column($jobs[$job]['steps'], 'run', 'name')['Create and push multi-arch manifest'];

        self::assertStringContainsString('-t "webdevops/base:latest"', $manifest('base_ubuntu-22-04_publish'));
        self::assertStringContainsString('-t "webdevops/php-official:8.4"', $manifest('php_8-4_publish'));
    }

    public function testPrintsTheImageTree(): void
    {
        $tester = new CommandTester(new GenerateGithubCiCommand($this->repository->path));
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString("Processing webdevops/toolbox:latest\n", $display);
        self::assertStringContainsString("\tProcessing webdevops/php:8.5-alpine\n", $display);
        self::assertStringContainsString("\t\t\tProcessing webdevops/php-nginx-dev:8.5-alpine\n", $display);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function missingInternalImages(): iterable
    {
        // e.g. php-official 8.5-alpine was removed, but not php-nginx 8.5-alpine
        yield 'base image' => [
            'docker/php-nginx/8.5-alpine/Dockerfile',
            "FROM webdevops/php:8.6-alpine\n",
            'webdevops/php-nginx:8.5-alpine (docker/php-nginx/8.5-alpine) builds on webdevops/php:8.6-alpine, which is not built any more. '
            . 'Remove docker/php-nginx/8.5-alpine too, or keep building webdevops/php:8.6-alpine.',
        ];
        yield 'image to copy from' => [
            'docker/base/alpine/Dockerfile',
            "FROM alpine:3.22\nCOPY --from=webdevops/tools:latest /bin/tool /bin/\n",
            'webdevops/base:alpine (docker/base/alpine) copies files from webdevops/tools:latest, which is not built any more. '
            . 'Remove docker/base/alpine too, or keep building webdevops/tools:latest.',
        ];
    }

    #[DataProvider('missingInternalImages')]
    public function testMissingInternalImageIsAnError(string $dockerfile, string $instructions, string $message): void
    {
        $header = $this->repository->read($dockerfile);
        $this->repository->write($dockerfile, substr($header, 0, (int)strpos($header, 'FROM ')) . $instructions);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        new CommandTester(new GenerateGithubCiCommand($this->repository->path))->execute([]);
    }

    /**
     * @return iterable<string, array{0: list<string>, 1: list<string>}>
     */
    public static function blacklists(): iterable
    {
        yield 'part of the name' => [['php-nginx'], ['php-nginx-dev_8-5-alpine', 'php-nginx_8-5-alpine']];
        yield 'image and tag' => [['php-nginx:8.5-alpine'], ['php-nginx_8-5-alpine']];
        yield 'start of the name' => [['webdevops/toolbox'], ['toolbox_latest']];
        yield 'the auto latest tag stays' => [['base'], ['base_alpine']];
        yield 'two entries' => [['php:8.4', 'base:alpine'], ['base_alpine', 'php_8-4']];
        yield 'surrounding whitespace' => [[' php-nginx:8.5-alpine ', "\tbase:alpine"], ['base_alpine', 'php-nginx_8-5-alpine']];
    }

    /**
     * @param list<string> $blacklist
     * @param list<string> $removed build jobs that are left out together with their publish job
     */
    #[DataProvider('blacklists')]
    public function testBlacklistedImagesAreLeftOut(array $blacklist, array $removed): void
    {
        $tester = new CommandTester(new GenerateGithubCiCommand($this->repository->path));
        $tester->execute(['--blacklist' => $blacklist]);

        self::assertSame($this->jobsWithout($removed), array_keys($this->workflow()['jobs']));
        self::assertStringContainsString(' *blacklisted*', $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{0: list<?string>}>
     */
    public static function emptyBlacklists(): iterable
    {
        yield 'empty entry' => [['']];
        yield 'blank entry' => [['  ']];
        yield 'option without value' => [[null]];
    }

    /**
     * @param list<?string> $blacklist
     */
    #[DataProvider('emptyBlacklists')]
    public function testEmptyBlacklistEntriesMatchNothing(array $blacklist): void
    {
        $tester = new CommandTester(new GenerateGithubCiCommand($this->repository->path));

        self::assertSame(Command::SUCCESS, $tester->execute(['--blacklist' => $blacklist]));
        self::assertSame(self::ALL_JOBS, array_keys($this->workflow()['jobs']));
        self::assertStringNotContainsString('*blacklisted*', $tester->getDisplay());
    }

    public function testBlacklistFile(): void
    {
        $this->repository->write('ci/BLACKLIST', "php-nginx-dev \n\n   \nphp:8.4\n");

        self::assertSame($this->jobsWithout(['php-nginx-dev_8-5-alpine', 'php_8-4']), array_keys($this->generate()['jobs']));
    }

    public function testBlacklistOptionReplacesTheFile(): void
    {
        $this->repository->write('ci/BLACKLIST', "php-nginx-dev\n");
        $tester = new CommandTester(new GenerateGithubCiCommand($this->repository->path));
        $tester->execute(['--blacklist' => ['php:8.4']]);

        self::assertSame($this->jobsWithout(['php_8-4']), array_keys($this->workflow()['jobs']));
    }

    /**
     * @return array<string, mixed>
     */
    private function generate(): array
    {
        $tester = new CommandTester(new GenerateGithubCiCommand($this->repository->path));
        self::assertSame(Command::SUCCESS, $tester->execute([]));
        return $this->workflow();
    }

    /**
     * @return array<string, mixed>
     */
    private function workflow(): array
    {
        return Yaml::parse($this->repository->read('.github/workflows/build.yaml'));
    }

    /**
     * @param list<string> $jobs
     * @return list<string>
     */
    private function jobsWithout(array $jobs): array
    {
        $publish = [];
        foreach ($jobs as $job) {
            $publish[] = $job . '_publish';
        }

        return array_values(array_diff(self::ALL_JOBS, $jobs, $publish));
    }
}
