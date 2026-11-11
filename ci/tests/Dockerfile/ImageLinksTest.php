<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Dockerfile;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\Dockerfile\ImageLinks;
use Webdevops\Build\TagDirectories;
use Webdevops\Build\Tests\TemporaryRepository;

use function is_link;
use function readlink;
use function scandir;
use function symlink;
use function unlink;

#[CoversClass(ImageLinks::class)]
final class ImageLinksTest extends TestCase
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

    public function testLinksEveryTagOfPhpOfficial(): void
    {
        unlink($this->repository->path . '/docker/php/8.5');

        $this->update();

        self::assertSame(['.', '..', '8.4', '8.5', '8.5-alpine'], scandir($this->repository->path . '/docker/php'));
        self::assertSame('../php-official/8.5', readlink($this->repository->path . '/docker/php/8.5'));
    }

    public function testRemovesLinksOfRemovedTags(): void
    {
        // e.g. php-official 8.3 was removed, its link not
        symlink('../php-official/8.3', $this->repository->path . '/docker/php/8.3');

        $this->update();

        self::assertFalse(is_link($this->repository->path . '/docker/php/8.3'));
    }

    public function testCorrectsALinkWithAnotherTarget(): void
    {
        unlink($this->repository->path . '/docker/php/8.5');
        symlink('../php-official/8.4', $this->repository->path . '/docker/php/8.5');

        $this->update();

        self::assertSame('../php-official/8.5', readlink($this->repository->path . '/docker/php/8.5'));
    }

    public function testKeepsALinkThatGitCheckedOutAsAFile(): void
    {
        unlink($this->repository->path . '/docker/php/8.5');
        $this->repository->write('docker/php/8.5', '../php-official/8.5');

        $this->update();

        self::assertSame('../php-official/8.5', $this->repository->read('docker/php/8.5'));
    }

    public function testADirectoryInThePlaceOfALinkIsAnError(): void
    {
        unlink($this->repository->path . '/docker/php/8.5');
        $this->repository->write('docker/php/8.5/Dockerfile', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('docker/php/8.5 has to be a symbolic link to ../php-official/8.5, but is a file or a directory.');
        $this->update();
    }

    private function update(): void
    {
        new ImageLinks($this->repository->path)->update(new TagDirectories($this->repository->path));
    }
}
