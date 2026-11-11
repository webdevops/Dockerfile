<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\FinderFile;

use function symlink;

#[CoversClass(FinderFile::class)]
final class FinderFileTest extends TestCase
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

    public function testRemovesEveryFinderFileUnderTheDirectory(): void
    {
        $this->repository->write('docker/.DS_Store', '');
        $this->repository->write('docker/base/alpine/.DS_Store', '');
        $this->repository->write('docker/toolbox/latest/conf/etc/.DS_Store', '');

        FinderFile::removeUnder($this->repository->path . '/docker');

        self::assertFileDoesNotExist($this->repository->path . '/docker/.DS_Store');
        self::assertFileDoesNotExist($this->repository->path . '/docker/base/alpine/.DS_Store');
        self::assertFileDoesNotExist($this->repository->path . '/docker/toolbox/latest/conf/etc/.DS_Store', 'a hand-made conf/ is copied into the image');
        self::assertSame("not provisioned\n", $this->repository->read('docker/toolbox/latest/conf/hand-made'));
    }

    public function testDoesNotFollowSymbolicLinks(): void
    {
        $this->repository->write('provisioning/base/general/.DS_Store', '');
        symlink('../provisioning', $this->repository->path . '/docker/provisioning');

        FinderFile::removeUnder($this->repository->path . '/docker');

        self::assertFileExists($this->repository->path . '/provisioning/base/general/.DS_Store');
    }

    public function testMissingDirectoryIsNoError(): void
    {
        FinderFile::removeUnder($this->repository->path . '/missing');

        self::assertDirectoryDoesNotExist($this->repository->path . '/missing');
    }
}
