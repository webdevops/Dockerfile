<?php

namespace Webdevops\Build;

use Webdevops\Build\DockerHub\Repository;

use function array_filter;
use function array_values;
use function dirname;
use function implode;
use function str_replace;

class GithubJobBuilder
{
    /**
     * Tag/annotation used when exporting a parent image as an OCI layout so
     * that child jobs can deterministically reference the exported manifest
     * instead of relying on Buildx picking an arbitrary entry from index.json.
     */
    private const string PARENT_OCI_TAG = 'ci-parent-image';

    /**
     * Local tag of the image that runs the image tests, built from ci/image-tests/Dockerfile. The job
     * IMAGE_TEST_RUNNER_JOB builds it once per architecture into the GitHub Actions cache; the image jobs load it from
     * there, so Composer only runs when ci/composer.lock changes and only one job writes the cache.
     */
    private const string IMAGE_TEST_RUNNER = 'webdevops-image-tests';

    public const string IMAGE_TEST_RUNNER_JOB = 'image-test-runner';

    public const string DOCKER_HUB_JOB = 'dockerhub-description';

    /**
     * Logs in to Docker Hub with DOCKERHUB_USERNAME and DOCKERHUB_TOKEN, masks the access token in the log and hands it
     * to the next steps as DOCKERHUB_ACCESS_TOKEN.
     */
    private const string DOCKER_HUB_LOGIN = <<<'BASH'
        token=$(jq -n --arg identifier "$DOCKERHUB_USERNAME" --arg secret "$DOCKERHUB_TOKEN" '{identifier: $identifier, secret: $secret}' \
          | curl -sS --fail-with-body --retry 3 -H 'Content-Type: application/json' --data @- "$DOCKER_HUB_API/auth/token" \
          | jq -r '.access_token // empty')
        if [ -z "$token" ]; then echo "::error::Docker Hub returned no access token"; exit 1; fi
        echo "::add-mask::$token"
        echo "DOCKERHUB_ACCESS_TOKEN=$token" >> "$GITHUB_ENV"
        BASH;

    /**
     * Sets the page (PAGE) and the short description (SHORT_DESCRIPTION) of a repository (REPOSITORY) on Docker Hub.
     */
    private const string DOCKER_HUB_UPLOAD = <<<'BASH'
        jq -n --rawfile full_description "$PAGE" --arg description "$SHORT_DESCRIPTION" \
          '{full_description: $full_description, description: $description}' \
          | curl -sS --fail-with-body --retry 3 -o /dev/null -w '%{http_code}\n' -X PATCH \
            -H 'Content-Type: application/json' -H "Authorization: Bearer $DOCKERHUB_ACCESS_TOKEN" \
            --data @- "$DOCKER_HUB_API/repositories/$REPOSITORY"
        BASH;

    /**
     * PIE resolves and downloads the PHP extensions through the GitHub API. Without a token, its requests count against
     * the limit for anonymous requests of the runner's IP address, and PIE fails with "Could not authenticate against
     * github.com". The build steps pass the token of the run as the secret COMPOSER_AUTH, which the RUN instructions
     * with PIE mount as an environment variable for Composer, on which PIE builds. Pull requests from forks get a
     * read-only token too.
     *
     * Every script of those RUN instructions can read the token, not only PIE: the installers of Composer and Blackfire
     * and the build systems of the extensions. In the image jobs it may push to ghcr.io (PUSH_PERMISSIONS), and a token
     * cannot have fewer permissions in one step. The token ends with the job.
     *
     * With the token, the PIE requests of all jobs and runs share one limit, that of GITHUB_TOKEN per repository,
     * instead of the anonymous limit of each runner's IP address: 7 extensions in 16 PHP jobs and Xdebug in 48
     * development jobs per run.
     */
    private const array COMPOSER_AUTH_ENV = [
        'COMPOSER_AUTH' => '{"github-oauth":{"github.com":"${{ secrets.GITHUB_TOKEN }}"}}',
    ];

    private const string COMPOSER_AUTH_SECRET = 'COMPOSER_AUTH=COMPOSER_AUTH';

    /**
     * The workflow gives the token only read access to the repository; the image jobs may also push their images to
     * ghcr.io. The token gets no other permission, because the build steps hand it to every script of the RUN
     * instructions with PIE (COMPOSER_AUTH_ENV).
     */
    private const array PUSH_PERMISSIONS = ['contents' => 'read', 'packages' => 'write'];

    /**
     * Every image is built for these platforms. The README banner lists them too.
     */
    public const array PLATFORMS = [
        [
            'arch' => 'amd64',
            'runner' => 'ubuntu-24.04',
            'platform' => 'linux/amd64',
        ],
        [
            'arch' => 'arm64',
            'runner' => 'ubuntu-24.04-arm',
            'platform' => 'linux/arm64',
        ],
    ];

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private readonly string $rootPath = __DIR__ . '/../..')
    {
    }

    /**
     * The build job and the publish job of a build tree node.
     *
     * @param array<string, mixed> $node node from FileReader::getInfo() with level and hasChildren
     * @return array<string, array<string, mixed>>
     */
    public function getJobsDescription(array $node): array
    {
        $imageTests = $this->imageTests($node);
        $structuredTests = $this->structuredTests($node);

        $jobId = GithubJobBuilder::toJobId($node['name']);
        $hasParent = (bool)($node['parent'] ?? null);
        $imageDependencies = $this->imageDependencies($node);
        $hasImageDependencies = $imageDependencies !== [];
        $hasChildren = !empty($node['hasChildren']);
        $parentJobId = $hasParent ? GithubJobBuilder::toJobId($node['parent']) : null;
        $needs = $hasParent ? $parentJobId . '_publish' : 'validate-automation';
        if ($imageTests !== null) {
            $needs = [$needs, self::IMAGE_TEST_RUNNER_JOB];
        }

        $pushTags = [];
        $pushTags[] = '-t "' . $node['id'] . '"';
        $pushTags[] = '-t "ghcr.io/' . $node['id'] . '"';
        foreach ($node['aliases'] as $alias) {
            $pushTags[] = '-t "' . $alias . '"';
            $pushTags[] = '-t "ghcr.io/' . $alias . '"';
        }

        // The images of every architecture that the image job pushed ("Push arch image")
        $archImages = array_map(
            fn (array $platform): string => '"' . $this->archImage($node['image'], $node['tag'], $platform['arch']) . '"',
            self::PLATFORMS,
        );
        $archImages[array_key_last($archImages)] .= ' && exit 0';

        return [
            $jobId => [
                'strategy' => [
                    'fail-fast' => false,
                    'matrix' => [
                        'include' => self::PLATFORMS,
                    ],
                ],
                'name' => $node['name'] . ' (${{ matrix.arch }})',
                'needs' => $needs,
                // even run if previous job skipped
                'if' => '${{ !failure() && !cancelled() }}',
                'runs-on' => '${{ matrix.runner }}',
                // pushes its image to ghcr.io on master and takes the parent images from there
                'permissions' => self::PUSH_PERMISSIONS,
                'container' => 'webdevops/dockerfile-build-env',
                'steps' => array_values(
                    array_filter(
                        [
                            [
                                'uses' => 'actions/checkout@v7',
                                'with' => ['persist-credentials' => false],
                            ],
                            ['uses' => 'docker/setup-buildx-action@v4'],
                            ...$this->downloadParentImageSteps($imageDependencies),
                            array_filter([
                                'name' => 'Build (load locally)',
                                'if' => $hasImageDependencies ? '${{ github.ref == \'refs/heads/master\' }}' : null,
                                'uses' => 'docker/build-push-action@v7',
                                'env' => self::COMPOSER_AUTH_ENV,
                                'with' => $this->buildPushWith($node),
                            ], fn (array|string|null $value): bool => $value !== null),
                            $hasImageDependencies ? [
                                'name' => 'Build (load locally, from parent artifact)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'docker/build-push-action@v7',
                                'env' => self::COMPOSER_AUTH_ENV,
                                'with' => array_merge(
                                    $this->buildPushWith($node),
                                    ['build-contexts' => $this->buildContexts($imageDependencies)],
                                ),
                            ] : null,
                            $imageTests ? [
                                'name' => 'Build the image test runner',
                                'uses' => 'docker/build-push-action@v7',
                                'with' => [
                                    'context' => '.',
                                    'file' => 'ci/image-tests/Dockerfile',
                                    'load' => true,
                                    'tags' => self::IMAGE_TEST_RUNNER,
                                    'cache-from' => $this->imageTestRunnerCache(),
                                ],
                            ] : null,
                            $imageTests ? [
                                'name' => 'run image tests',
                                'run' => $imageTests,
                            ] : null,
                            $structuredTests ? [
                                'name' => 'run structure-test',
                                'run' => implode("\n", $structuredTests),
                            ] : null,
                            [
                                'if' => '${{github.ref == \'refs/heads/master\'}}',
                                ...$this->loginGhcrStep(),
                            ],
                            [
                                'name' => 'Push arch image',
                                'if' => '${{github.ref == \'refs/heads/master\'}}',
                                'run' => 'docker push "' . $this->archImage($node['image'], $node['tag']) . '"',
                            ],
                            $hasChildren ? [
                                'name' => 'Export image (OCI layout)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'docker/build-push-action@v7',
                                // Only needed if a layer is not in the cache after all.
                                'env' => self::COMPOSER_AUTH_ENV,
                                // Reuse the tested local build, including on no-cache reruns.
                                'with' => array_merge(
                                    [
                                        'context' => $this->context($node),
                                        'platforms' => '${{ matrix.platform }}',
                                        ...$this->buildCacheWith($node),
                                        'build-args' => implode("\n", [
                                            'TARGETARCH=${{ matrix.arch }}',
                                        ]),
                                        'secret-envs' => self::COMPOSER_AUTH_SECRET,
                                    ],
                                    // Must use the exact same build-contexts override as the
                                    // tested build above, otherwise this second Buildx invocation
                                    // silently re-resolves FROM/COPY --from references against the
                                    // published registry images and the exported artifact no
                                    // longer reflects the tested result.
                                    $hasImageDependencies ? ['build-contexts' => $this->buildContexts($imageDependencies)] : [],
                                    ['outputs' => 'type=oci,tar=false,name=' . self::PARENT_OCI_TAG . ',dest=' . $this->getCiImagePath($node['id'])],
                                ),
                            ] : null,
                            $hasChildren ? [
                                'name' => 'Upload image (OCI layout)',
                                'if' => '${{ github.ref != \'refs/heads/master\' }}',
                                'uses' => 'actions/upload-artifact@v7',
                                'with' => [
                                    'name' => $this->getCiImageArtifactName($node['id']),
                                    'path' => $this->getCiImagePath($node['id']),
                                    'retention-days' => 1,
                                    // A rerun must replace the previous attempt's parent image.
                                    'overwrite' => true,
                                    'compression-level' => 0,
                                    'if-no-files-found' => 'error',
                                ],
                            ] : null,
                        ],
                    ),
                ),
            ],
            $jobId . '_publish' => [
                'name' => $node['name'] . ' - Publish',
                'runs-on' => 'ubuntu-latest',
                'permissions' => ['packages' => 'write'],
                'needs' => $jobId,
                'if' => '${{github.ref == \'refs/heads/master\'}}',
                'steps' => [
                    ['uses' => 'docker/setup-buildx-action@v4'],
                    $this->loginGhcrStep(),
                    [
                        'name' => 'Login to hub.docker.com',
                        'uses' => 'docker/login-action@v4',
                        'with' => [
                            'username' => '${{ secrets.DOCKERHUB_USERNAME }}',
                            'password' => '${{ secrets.DOCKERHUB_TOKEN }}',
                        ],
                    ],
                    [
                        'name' => 'Create and push multi-arch manifest',
                        'run' =>
                        // we need the retry loop here because sometimes docker hub returns errors when pushing manifests (especially if pushed to the same image multiple times in a short time frame)
                            implode("\n", [
                                'set -euo pipefail',
                                'for i in 1 2 3 4 5 6 7 8 9 10; do',
                                '  ' . implode(" \\\n  ", [
                                    'docker buildx imagetools create',
                                    ...$pushTags,
                                    ...$archImages,
                                ]),
                                '  sleep $((i*i))',
                                'done',
                                'exit 1',
                            ]),
                    ],
                ],
            ],
        ];
    }

    public static function toJobId(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace('webdevops/', '', $name);
        $name = str_replace(['/', '.'], '-', $name);
        return str_replace(':', '_', $name);
    }

    /**
     * The directory of the node's Dockerfile, relative to the repository root like the file itself.
     *
     * @param array<string, mixed> $node
     */
    private function context(array $node): string
    {
        return dirname($node['file']);
    }

    /**
     * Common `docker/build-push-action` inputs shared by the local build step
     * and the additional per-node variants (parent-context build, OCI export).
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function buildPushWith(array $node): array
    {
        return [
            'context' => $this->context($node),
            'platforms' => '${{ matrix.platform }}',
            'load' => true,
            'tags' => $this->archImage($node['image'], $node['tag']),
            // Re-run all jobs to test a fresh build of the full image chain.
            'no-cache' => '${{ github.event_name == \'schedule\' || github.run_attempt > 1 }}',
            ...$this->buildCacheWith($node),
            'build-args' => implode("\n", [
                'TARGETARCH=${{ matrix.arch }}',
            ]),
            'secret-envs' => self::COMPOSER_AUTH_SECRET,
        ];
    }

    /**
     * The image of one architecture that the job of webdevops/$image:$tag builds and, on master, pushes to ghcr.io,
     * e.g. "ghcr.io/webdevops/php:sha-<commit>-amd64-8.4"; by default the architecture of the matrix job.
     */
    private function archImage(string $image, string $tag, string $arch = '${{ matrix.arch }}'): string
    {
        return 'ghcr.io/webdevops/' . $image . ':sha-${{ github.sha }}-' . $arch . '-' . $tag;
    }

    /**
     * Logs in to ghcr.io with the token of the run, to push the images there.
     *
     * @return array<string, mixed>
     */
    private function loginGhcrStep(): array
    {
        return [
            'name' => 'Login to ghcr.io',
            'uses' => 'docker/login-action@v4',
            'with' => [
                'registry' => 'ghcr.io',
                'username' => '${{ github.actor }}',
                'password' => '${{ secrets.GITHUB_TOKEN }}',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array{cache-from: string, cache-to: string}
     */
    private function buildCacheWith(array $node): array
    {
        // Full image/tag and architecture keep scopes unique and reusable
        // across commits, branches and reruns.
        $cache = 'type=gha,scope=' . $node['id'] . '-${{ matrix.arch }}';
        return [
            'cache-from' => $cache,
            'cache-to' => $cache . ',mode=max',
        ];
    }

    /**
     * All internal webdevops/* images this node needs a same-run OCI
     * artifact override for: its real FROM parent (if any) plus any images
     * referenced via `COPY --from=webdevops/...`. Keyed by the *literal*
     * reference text as written in the Dockerfile (the key a Buildx
     * `build-contexts` override must match exactly), valued by the resolved
     * image id that identifies the job/artifact actually producing it.
     *
     * `node['parent']` is NOT used here on its own: it may be a purely
     * synthetic scheduling dependency (e.g. on the Toolbox job for images
     * whose Dockerfile does not actually FROM/COPY an internal image), which
     * must never drive OCI artifact propagation.
     *
     * @param array<string, mixed> $node
     * @return array<string, string>
     */
    private function imageDependencies(array $node): array
    {
        $dependencies = [];
        if (!empty($node['imageParent'])) {
            $literalRef = $node['imageParentRef'] ?: $node['imageParent'];
            $dependencies[$literalRef] = $node['imageParent'];
        }

        foreach ($node['imageDependencies'] ?? [] as $literalRef => $resolvedImage) {
            $dependencies[$literalRef] = $resolvedImage;
        }

        return $dependencies;
    }

    /**
     * One "Download parent image (OCI layout)" step per distinct image id
     * referenced in $imageDependencies, deduplicated so the same artifact is
     * never downloaded twice (e.g. if an image happens to be both the FROM
     * parent and a COPY --from target).
     *
     * @param array<string, string> $imageDependencies
     * @return array<int, array<string, mixed>>
     */
    private function downloadParentImageSteps(array $imageDependencies): array
    {
        $imageIds = array_unique(array_values($imageDependencies));
        return array_map(fn (string $imageId): array => [
            'name' => 'Download parent image (OCI layout): ' . $imageId,
            'if' => '${{ github.ref != \'refs/heads/master\' }}',
            'uses' => 'actions/download-artifact@v8',
            'with' => [
                'name' => $this->getCiImageArtifactName($imageId),
                'path' => $this->getCiImagePath($imageId),
            ],
        ], $imageIds);
    }

    /**
     * Deterministic, filesystem/artifact-safe name for the image identified
     * by $imageId (e.g. "webdevops/php:8.4"), unique per architecture so
     * amd64/arm64 artifacts can never collide or be cross-consumed.
     */
    private function getCiImageArtifactName(string $imageId): string
    {
        return 'docker-parent-' . GithubJobBuilder::toJobId($imageId) . '-${{ matrix.arch }}';
    }

    /**
     * Predictable, collision-free extraction/export directory for the OCI
     * layout of the image identified by $imageId.
     *
     * Intentionally a path relative to the job's working directory (the
     * checked-out repository) rather than an absolute `${{ runner.temp }}`
     * path: every job in this workflow runs inside a `container:`, and
     * `${{ runner.temp }}` is evaluated by the Actions runner against the
     * *host* filesystem, which is only bind-mounted into the container under
     * `/__w/_temp`, not under the literal host path. A relative path is
     * resolved consistently by every step (checkout, Buildx, up-/download-artifact)
     * against the same container working directory, avoiding that mismatch.
     */
    private function getCiImagePath(string $imageId): string
    {
        return '.ci-oci-image/' . GithubJobBuilder::toJobId($imageId);
    }

    /**
     * Buildx `build-contexts` value mapping every literal FROM/COPY --from
     * reference in $imageDependencies to the OCI layout downloaded from the
     * corresponding parent job's artifact, so BuildKit never needs to pull
     * any of those images from Docker Hub during a non-master build. Must
     * be reused unchanged for every Buildx invocation of this node (tested
     * build and OCI export alike), otherwise a later invocation silently
     * resolves a dependency from the registry again.
     *
     * @param array<string, string> $imageDependencies
     */
    private function buildContexts(array $imageDependencies): string
    {
        $lines = [];
        foreach ($imageDependencies as $literalRef => $imageId) {
            $lines[] = $literalRef . '=oci-layout://' . $this->getCiImagePath($imageId) . ':' . self::PARENT_OCI_TAG;
        }

        return implode("\n", $lines);
    }

    /**
     * Command of the image test step, null if the image has no tests in ci/image-tests: make test-image-run, the command
     * of the docs, runs the tests against the image this job built, with the runner that the step before built.
     *
     * @param array<string, mixed> $node
     */
    private function imageTests(array $node): ?string
    {
        if (new ImageTests($this->rootPath)->testFile($node['image']) === null) {
            return null;
        }

        return 'make test-image-run IMAGE=' . $node['id']
            . ' REFERENCE=' . $this->archImage($node['image'], $node['tag']);
    }

    /**
     * Shell lines of the container-structure-test step, none if the image has no suite.
     *
     * @param array<string, mixed> $node
     * @return list<string>
     */
    private function structuredTests(array $node): array
    {
        $script = [];
        if (file_exists($this->rootPath . '/tests/structure-test/' . $node['image'] . '/test.yaml')) {
            $script[] = 'cd tests/structure-test';
            if (file_exists($this->rootPath . '/tests/structure-test/' . $node['image'] . '/' . $node['tag'] . '/test.yaml')) {
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $this->archImage($node['image'], $node['tag']) . ' --config ' . $node['image'] . '/test.yaml --config ' . $node['image'] . '/' . $node['tag'] . '/test.yaml';
            } else {
                $script[] = '/usr/local/bin/container-structure-test test --image ' . $this->archImage($node['image'], $node['tag']) . ' --config ' . $node['image'] . '/test.yaml';
            }
        }

        return $script;
    }

    /**
     * Builds the image test runner once per architecture into the GitHub Actions cache, from which the image jobs load
     * it. It runs while the first images are built.
     *
     * @return array<string, mixed>
     */
    public function getImageTestRunnerConfig(): array
    {
        return [
            'strategy' => [
                'fail-fast' => false,
                'matrix' => [
                    'include' => self::PLATFORMS,
                ],
            ],
            'name' => 'Image test runner (${{ matrix.arch }})',
            'runs-on' => '${{ matrix.runner }}',
            'steps' => [
                [
                    'uses' => 'actions/checkout@v7',
                    'with' => ['persist-credentials' => false],
                ],
                ['uses' => 'docker/setup-buildx-action@v4'],
                [
                    'name' => 'Build the image test runner',
                    'uses' => 'docker/build-push-action@v7',
                    // Only warms the cache: if it fails, e.g. on a transient error of the cache or of Docker Hub, the
                    // image jobs that need this job still run and build the runner themselves.
                    'continue-on-error' => true,
                    'with' => [
                        'context' => '.',
                        'file' => 'ci/image-tests/Dockerfile',
                        'platforms' => '${{ matrix.platform }}',
                        'outputs' => 'type=cacheonly',
                        'cache-from' => $this->imageTestRunnerCache(),
                        'cache-to' => $this->imageTestRunnerCache() . ',mode=max',
                    ],
                ],
            ],
        ];
    }

    private function imageTestRunnerCache(): string
    {
        return 'type=gha,scope=' . self::IMAGE_TEST_RUNNER . '-${{ matrix.arch }}';
    }

    /**
     * Checks the PHP code in ci/ with GrumPHP: coding standard, PHPStan, Rector, composer audit and PHPUnit.
     * No other job waits for it, so a finding does not stop the images from being built.
     *
     * @return array<string, mixed>
     */
    public function getCodeCheckConfig(): array
    {
        return [
            'name' => 'Check ci/ (GrumPHP and PHPUnit)',
            'runs-on' => 'ubuntu-latest',
            'steps' => [
                [
                    'uses' => 'actions/checkout@v7',
                    'with' => ['persist-credentials' => false],
                ],
                [
                    'name' => 'Run GrumPHP',
                    // The command of the documentation
                    'run' => 'make check',
                ],
            ],
        ];
    }

    /**
     * Uploads the page and the short description of every Docker Hub repository (docs/dockerhub/, written by
     * ci/console generate:dockerhub) after a push to master and on a manual run, only in webdevops/Dockerfile: pull
     * requests get no secrets, the nightly run changes no page, and a fork must not overwrite the pages. The builds
     * that .github/workflows/php-upstream.yaml starts (actor github-actions[bot]) skip it too: that workflow starts no
     * further build for hours after one of them failed, and a failed upload must not hold back new PHP releases.
     *
     * The job waits for no image and no image waits for it: a failed build does not hold back the pages, a failed
     * upload no image. Each repository is a step that runs even if an earlier one failed, so one rejected page does not
     * keep the others from being updated; the job then fails.
     *
     * Docker Hub only changes the description for a user with admin rights on the repository, with a password or a
     * personal access token with the scope Read/Write/Delete. The steps call the API of Docker Hub themselves, as
     * peter-evans/dockerhub-description does, so that no action from outside GitHub gets the token.
     *
     * @param list<Repository> $repositories
     * @return array<string, mixed>
     */
    public function getDockerHubDescriptionConfig(array $repositories): array
    {
        $steps = [
            [
                'uses' => 'actions/checkout@v7',
                'with' => ['persist-credentials' => false],
            ],
            [
                'name' => 'Log in to Docker Hub',
                'id' => 'login',
                'shell' => 'bash',
                'env' => [
                    'DOCKERHUB_USERNAME' => '${{ secrets.DOCKERHUB_USERNAME }}',
                    'DOCKERHUB_TOKEN' => '${{ secrets.DOCKERHUB_TOKEN }}',
                ],
                'run' => self::DOCKER_HUB_LOGIN,
            ],
        ];
        foreach ($repositories as $repository) {
            $image = 'webdevops/' . $repository->name;
            $steps[] = [
                'name' => $image,
                'if' => '${{ !cancelled() && steps.login.outcome == \'success\' }}',
                'shell' => 'bash',
                'env' => [
                    'REPOSITORY' => $image,
                    'PAGE' => $repository->pagePath(),
                    'SHORT_DESCRIPTION' => $repository->shortDescription,
                ],
                'run' => self::DOCKER_HUB_UPLOAD,
            ];
        }

        return [
            'name' => 'Update the Docker Hub pages',
            'if' => '${{ github.repository == \'webdevops/Dockerfile\' && github.ref == \'refs/heads/master\''
                . " && (github.event_name == 'push'"
                . " || (github.event_name == 'workflow_dispatch' && github.actor != 'github-actions[bot]')) }}",
            'runs-on' => 'ubuntu-latest',
            'env' => ['DOCKER_HUB_API' => 'https://hub.docker.com/v2'],
            'steps' => $steps,
        ];
    }

    /**
     * The first job, which checks that the generated files are up to date.
     *
     * @return array<string, mixed>
     */
    public function getValidationConfig(): array
    {
        return [
            'name' => 'Validate Automation',
            'runs-on' => 'ubuntu-latest',
            'steps' => [
                [
                    'uses' => 'actions/checkout@v7',
                    'with' => ['persist-credentials' => false],
                ],
                [
                    'name' => 'Validate that the generated files are up to date',
                    'run' => implode("\n", [
                        // The command of the documentation
                        'make generate',
                        // A changed or a new file fails the step
                        'git status --porcelain',
                        'git diff --exit-code --color=always',
                        'test -z "$(git status --porcelain)"',
                    ]),
                ],
            ],
        ];
    }
}
