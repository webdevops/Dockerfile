<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\TagDirectories;

use function mkdir;

#[CoversClass(TagDirectories::class)]
final class TagDirectoriesTest extends TestCase
{
    private TemporaryRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TemporaryRepository();
    }

    protected function tearDown(): void
    {
        $this->repository->remove();
    }

    public function testTagsAreTheDirectoriesWithATemplateSortedLikeTheirDockerfiles(): void
    {
        // docker/php/8.4 links to docker/php-official/8.4 and is left out
        self::assertSame([
            'docker/base-app/ubuntu-22.04',
            'docker/base/alpine',
            'docker/base/ubuntu-22.04',
            'docker/php-nginx-dev/8.5-alpine',
            'docker/php-nginx/8.5-alpine',
            'docker/php-official/8.4',
            'docker/php-official/8.5-alpine',
            'docker/php-official/8.5',
            'docker/toolbox/latest',
        ], $this->tagDirectories()->tags());
    }

    public function testTagsOfOneImage(): void
    {
        self::assertSame(['docker/base/alpine', 'docker/base/ubuntu-22.04'], $this->tagDirectories()->tags('base'));
        self::assertSame([], $this->tagDirectories()->tags('php'), 'every tag of php is a symbolic link');
        self::assertSame([], $this->tagDirectories()->tags('missing'));
    }

    public function testDirectoriesWithoutTemplateAreNoTags(): void
    {
        $this->repository->write('docker/base/alpine-edge/Dockerfile', '');
        $this->repository->write('docker/base/alpine-old/conf/etc/os', '');
        $this->repository->write('docker/base/Dockerfile.twig', '');
        $this->repository->write('docker/base/alpine/conf/sub/Dockerfile.twig', '');
        mkdir($this->repository->path . '/docker/base/empty');

        self::assertSame(['docker/base/alpine', 'docker/base/ubuntu-22.04'], $this->tagDirectories()->tags('base'), 'a template in docker/base/ is no tag, one in alpine/conf/sub is too deep');
        self::assertSame(['docker/base/alpine-edge', 'docker/base/alpine-old'], $this->tagDirectories()->withoutTemplate(), 'an empty directory is left alone');
    }

    public function testNoDirectoryWithoutTemplateInTheFixture(): void
    {
        self::assertSame([], $this->tagDirectories()->withoutTemplate());
    }

    private function tagDirectories(): TagDirectories
    {
        return new TagDirectories($this->repository->path);
    }
}
