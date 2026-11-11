<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\GithubJobBuilder;

use function array_filter;
use function array_keys;
use function str_starts_with;

#[CoversClass(GithubJobBuilder::class)]
final class GithubJobBuilderTest extends TestCase
{
    private const string ROOT = TemporaryRepository::FIXTURE;

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function jobIds(): iterable
    {
        yield 'image and tag' => ['webdevops/php-nginx:8.5-alpine', 'php-nginx_8-5-alpine'];
        yield 'upper case' => ['webdevops/PHP:8.4', 'php_8-4'];
        yield 'path' => ['webdevops/php/8.4', 'php-8-4'];
    }

    #[DataProvider('jobIds')]
    public function testJobId(string $name, string $expected): void
    {
        self::assertSame($expected, GithubJobBuilder::toJobId($name));
    }

    public function testBuildAndPublishJob(): void
    {
        $jobs = $this->jobs();

        self::assertSame(['base_alpine', 'base_alpine_publish'], array_keys($jobs));
        self::assertSame('webdevops/base:alpine (${{ matrix.arch }})', $jobs['base_alpine']['name']);
        self::assertSame(['fail-fast' => false, 'matrix' => ['include' => GithubJobBuilder::PLATFORMS]], $jobs['base_alpine']['strategy']);
        self::assertSame('${{ matrix.runner }}', $jobs['base_alpine']['runs-on']);
        self::assertSame('webdevops/dockerfile-build-env', $jobs['base_alpine']['container']);
        self::assertSame('base_alpine', $jobs['base_alpine_publish']['needs']);
        self::assertSame('${{github.ref == \'refs/heads/master\'}}', $jobs['base_alpine_publish']['if']);
    }

    public function testJobWaitsForItsParentAndTheImageTestRunner(): void
    {
        self::assertSame(['toolbox_latest_publish', 'image-test-runner'], $this->jobs()['base_alpine']['needs']);
        self::assertSame(['validate-automation', 'image-test-runner'], $this->jobs(['parent' => 0])['base_alpine']['needs']);
        self::assertSame('toolbox_latest_publish', $this->jobs(['image' => 'php-nginx'])['base_alpine']['needs'], 'no image tests, no runner');
    }

    public function testImageTestRunnerIsBuiltOncePerArchitectureIntoTheCache(): void
    {
        $job = new GithubJobBuilder(self::ROOT)->getImageTestRunnerConfig();

        self::assertSame(['fail-fast' => false, 'matrix' => ['include' => GithubJobBuilder::PLATFORMS]], $job['strategy']);
        self::assertSame('${{ matrix.runner }}', $job['runs-on']);
        self::assertSame([
            'context' => '.',
            'file' => 'ci/image-tests/Dockerfile',
            'platforms' => '${{ matrix.platform }}',
            'outputs' => 'type=cacheonly',
            'cache-from' => 'type=gha,scope=webdevops-image-tests-${{ matrix.arch }}',
            'cache-to' => 'type=gha,scope=webdevops-image-tests-${{ matrix.arch }},mode=max',
        ], $job['steps'][2]['with']);
    }

    public function testBuildsTheDirectoryOfTheDockerfile(): void
    {
        $with = $this->step($this->jobs(), 'Build (load locally)')['with'];

        self::assertSame('docker/base/alpine', $with['context']);
        self::assertSame('${{ matrix.platform }}', $with['platforms']);
        self::assertTrue($with['load']);
        self::assertSame('ghcr.io/webdevops/base:sha-${{ github.sha }}-${{ matrix.arch }}-alpine', $with['tags']);
        self::assertSame('type=gha,scope=webdevops/base:alpine-${{ matrix.arch }}', $with['cache-from']);
        self::assertSame('type=gha,scope=webdevops/base:alpine-${{ matrix.arch }},mode=max', $with['cache-to']);
        self::assertSame('TARGETARCH=${{ matrix.arch }}', $with['build-args']);
    }

    public function testImageWithoutDependenciesIsBuiltOnce(): void
    {
        $steps = $this->steps($this->jobs());

        self::assertSame([
            'actions/checkout@v7',
            'docker/setup-buildx-action@v4',
            'Build (load locally)',
            'Build the image test runner',
            'run image tests',
            'run structure-test',
            'Login to ghcr.io',
            'Push arch image',
        ], array_keys($steps));
        self::assertArrayNotHasKey('if', $steps['Build (load locally)']);
        self::assertSame(
            'docker push "ghcr.io/webdevops/base:sha-${{ github.sha }}-${{ matrix.arch }}"-alpine',
            $steps['Push arch image']['run'],
        );
    }

    public function testImageTestsRunAgainstTheImageOfTheJob(): void
    {
        $steps = $this->steps($this->jobs());

        self::assertSame([
            'context' => '.',
            'file' => 'ci/image-tests/Dockerfile',
            'load' => true,
            'tags' => 'webdevops-image-tests',
            // the job image-test-runner writes the cache, the image jobs only read it
            'cache-from' => 'type=gha,scope=webdevops-image-tests-${{ matrix.arch }}',
        ], $steps['Build the image test runner']['with']);
        self::assertSame(
            'make test-image-run IMAGE=webdevops/base:alpine REFERENCE=ghcr.io/webdevops/base:sha-${{ github.sha }}-${{ matrix.arch }}-alpine',
            $steps['run image tests']['run'],
        );
    }

    public function testOfficialPhpImageRunsTheTestsOfPhp(): void
    {
        $steps = $this->steps($this->jobs([
            'id' => 'webdevops/php:8.4',
            'name' => 'webdevops/php:8.4',
            'image' => 'php',
            'tag' => '8.4',
            'aliases' => ['webdevops/php-official:8.4'],
        ]), 'php_8-4');

        self::assertSame(
            'make test-image-run IMAGE=webdevops/php:8.4 REFERENCE=ghcr.io/webdevops/php:sha-${{ github.sha }}-${{ matrix.arch }}-8.4',
            $steps['run image tests']['run'],
        );
    }

    public function testStructureTestUsesTheSuiteOfTheTagToo(): void
    {
        self::assertSame(
            "cd tests/structure-test\n/usr/local/bin/container-structure-test test --image ghcr.io/webdevops/base:sha-\${{ github.sha }}-\${{ matrix.arch }}-alpine"
            . ' --config base/test.yaml --config base/alpine/test.yaml',
            $this->step($this->jobs(), 'run structure-test')['run'],
        );
        self::assertSame(
            "cd tests/structure-test\n/usr/local/bin/container-structure-test test --image ghcr.io/webdevops/base:sha-\${{ github.sha }}-\${{ matrix.arch }}-ubuntu-22.04"
            . ' --config base/test.yaml',
            $this->step($this->jobs(['tag' => 'ubuntu-22.04']), 'run structure-test')['run'],
        );
    }

    public function testImageWithoutTestsHasNoTestSteps(): void
    {
        $steps = $this->steps($this->jobs(['image' => 'php-nginx']));

        self::assertArrayNotHasKey('Build the image test runner', $steps);
        self::assertArrayNotHasKey('run image tests', $steps);
        self::assertArrayNotHasKey('run structure-test', $steps);
    }

    public function testBranchBuildUsesTheParentImagesOfThisRun(): void
    {
        $steps = $this->steps($this->jobs([
            'imageParent' => 'webdevops/base:ubuntu-22.04',
            'imageParentRef' => 'webdevops/base:latest',
            'imageDependencies' => ['webdevops/toolbox' => 'webdevops/toolbox:latest'],
        ]));

        self::assertSame([
            'actions/checkout@v7',
            'docker/setup-buildx-action@v4',
            'Download parent image (OCI layout): webdevops/base:ubuntu-22.04',
            'Download parent image (OCI layout): webdevops/toolbox:latest',
            'Build (load locally)',
            'Build (load locally, from parent artifact)',
            'Build the image test runner',
            'run image tests',
            'run structure-test',
            'Login to ghcr.io',
            'Push arch image',
        ], array_keys($steps));
        self::assertSame(
            ['name' => 'docker-parent-base_ubuntu-22-04-${{ matrix.arch }}', 'path' => '.ci-oci-image/base_ubuntu-22-04'],
            $steps['Download parent image (OCI layout): webdevops/base:ubuntu-22.04']['with'],
        );
        self::assertSame('${{ github.ref != \'refs/heads/master\' }}', $steps['Download parent image (OCI layout): webdevops/base:ubuntu-22.04']['if']);
        self::assertSame('${{ github.ref == \'refs/heads/master\' }}', $steps['Build (load locally)']['if']);
        self::assertArrayNotHasKey('build-contexts', $steps['Build (load locally)']['with']);
        self::assertSame('${{ github.ref != \'refs/heads/master\' }}', $steps['Build (load locally, from parent artifact)']['if']);
        self::assertSame(
            "webdevops/base:latest=oci-layout://.ci-oci-image/base_ubuntu-22-04:ci-parent-image\n"
            . 'webdevops/toolbox=oci-layout://.ci-oci-image/toolbox_latest:ci-parent-image',
            $steps['Build (load locally, from parent artifact)']['with']['build-contexts'],
        );
    }

    public function testParentThatIsAlsoCopiedFromIsDownloadedOnce(): void
    {
        $steps = $this->steps($this->jobs([
            'imageParent' => 'webdevops/toolbox:latest',
            'imageParentRef' => 'webdevops/toolbox:latest',
            'imageDependencies' => ['webdevops/toolbox' => 'webdevops/toolbox:latest'],
        ]));

        self::assertArrayHasKey('Download parent image (OCI layout): webdevops/toolbox:latest', $steps);
        self::assertSame(
            "webdevops/toolbox:latest=oci-layout://.ci-oci-image/toolbox_latest:ci-parent-image\n"
            . 'webdevops/toolbox=oci-layout://.ci-oci-image/toolbox_latest:ci-parent-image',
            $steps['Build (load locally, from parent artifact)']['with']['build-contexts'],
        );
        self::assertCount(1, array_filter(array_keys($steps), fn (string $name): bool => str_starts_with($name, 'Download')));
    }

    public function testImageWithChildrenPassesItselfOn(): void
    {
        $steps = $this->steps($this->jobs(['hasChildren' => true, 'imageDependencies' => ['webdevops/toolbox' => 'webdevops/toolbox:latest']]));

        $export = $steps['Export image (OCI layout)'];
        self::assertSame('${{ github.ref != \'refs/heads/master\' }}', $export['if']);
        self::assertSame('docker/base/alpine', $export['with']['context']);
        self::assertSame('webdevops/toolbox=oci-layout://.ci-oci-image/toolbox_latest:ci-parent-image', $export['with']['build-contexts']);
        self::assertSame('type=oci,tar=false,name=ci-parent-image,dest=.ci-oci-image/base_alpine', $export['with']['outputs']);
        self::assertSame([
            'name' => 'docker-parent-base_alpine-${{ matrix.arch }}',
            'path' => '.ci-oci-image/base_alpine',
            'retention-days' => 1,
            'overwrite' => true,
            'compression-level' => 0,
            'if-no-files-found' => 'error',
        ], $steps['Upload image (OCI layout)']['with']);
        self::assertArrayNotHasKey('Export image (OCI layout)', $this->steps($this->jobs()));
    }

    public function testPublishPushesEveryTagToBothRegistries(): void
    {
        $node = ['id' => 'webdevops/base:ubuntu-22.04', 'name' => 'webdevops/base:ubuntu-22.04', 'tag' => 'ubuntu-22.04', 'aliases' => ['webdevops/base:latest']];
        $steps = $this->steps($this->jobs($node), 'base_ubuntu-22-04_publish');

        self::assertSame(['docker/setup-buildx-action@v4', 'Login to ghcr.io', 'Login to hub.docker.com', 'Create and push multi-arch manifest'], array_keys($steps));
        self::assertSame(
            "set -euo pipefail\n"
            . "for i in 1 2 3 4 5 6 7 8 9 10; do\n"
            . "  docker buildx imagetools create \\\n"
            . "  -t \"webdevops/base:ubuntu-22.04\" \\\n"
            . "  -t \"ghcr.io/webdevops/base:ubuntu-22.04\" \\\n"
            . "  -t \"webdevops/base:latest\" \\\n"
            . "  -t \"ghcr.io/webdevops/base:latest\" \\\n"
            . "  \"ghcr.io/webdevops/base:sha-\${{ github.sha }}-amd64-ubuntu-22.04\" \\\n"
            . "  \"ghcr.io/webdevops/base:sha-\${{ github.sha }}-arm64-ubuntu-22.04\" && exit 0\n"
            . "  sleep \$((i*i))\n"
            . "done\n"
            . 'exit 1',
            $steps['Create and push multi-arch manifest']['run'],
        );
    }

    public function testValidationJobChecksTheGeneratedFiles(): void
    {
        $job = new GithubJobBuilder(self::ROOT)->getValidationConfig();
        $steps = $this->steps(['job' => $job], 'job');

        self::assertSame('Validate Automation', $job['name']);
        self::assertSame(['actions/checkout@v7', 'Validate that the generated files are up to date'], array_keys($steps));
        self::assertSame(
            "make generate\n"
            . "git status --porcelain\n"
            . "git diff --exit-code --color=always\n"
            . 'test -z "$(git status --porcelain)"',
            $steps['Validate that the generated files are up to date']['run'],
            'the command of the documentation, and a new file fails the step too',
        );
    }

    public function testCodeCheckRunsGrumphp(): void
    {
        $job = new GithubJobBuilder(self::ROOT)->getCodeCheckConfig();
        $steps = $this->steps(['job' => $job], 'job');

        self::assertArrayNotHasKey('needs', $job);
        self::assertSame(['actions/checkout@v7', 'Run GrumPHP'], array_keys($steps));
        self::assertSame('make check', $steps['Run GrumPHP']['run'], 'the command of the documentation');
    }

    /**
     * Jobs of the fixture's webdevops/base:alpine, as FileReader and GenerateGithubCiCommand describe it.
     *
     * @param array<string, mixed> $node replaces values of the node
     * @return array<string, array<string, mixed>>
     */
    private function jobs(array $node = []): array
    {
        $node = [
            'id' => 'webdevops/base:alpine',
            'name' => 'webdevops/base:alpine',
            'image' => 'base',
            'tag' => 'alpine',
            'aliases' => [],
            'file' => 'docker/base/alpine/Dockerfile',
            'parent' => 'webdevops/toolbox:latest',
            'imageParent' => 0,
            'imageParentRef' => null,
            'imageDependencies' => [],
            'baseImage' => 'alpine:3.22',
            'level' => 2,
            'hasChildren' => false,
            ...$node,
        ];
        return new GithubJobBuilder(self::ROOT)->getJobsDescription($node);
    }

    /**
     * The steps of a job by name, or by action for steps without a name.
     *
     * @param array<string, array<string, mixed>> $jobs
     * @return array<string, array<string, mixed>>
     */
    private function steps(array $jobs, string $job = 'base_alpine'): array
    {
        $steps = [];
        foreach ($jobs[$job]['steps'] as $step) {
            $steps[$step['name'] ?? $step['uses']] = $step;
        }

        return $steps;
    }

    /**
     * @param array<string, array<string, mixed>> $jobs
     * @return array<string, mixed>
     */
    private function step(array $jobs, string $name): array
    {
        $steps = $this->steps($jobs);
        self::assertArrayHasKey($name, $steps);
        return $steps[$name];
    }
}
