<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\FileReader;

use function str_replace;
use function symlink;

#[CoversClass(FileReader::class)]
final class FileReaderTest extends TestCase
{
    private const string ROOT = TemporaryRepository::FIXTURE;

    public function testCollectsTheGeneratedDockerfilesSorted(): void
    {
        self::assertSame([
            'docker/base-app/ubuntu-22.04/Dockerfile',
            'docker/base/alpine/Dockerfile',
            'docker/base/ubuntu-22.04/Dockerfile',
            'docker/php-nginx-dev/8.5-alpine/Dockerfile',
            'docker/php-nginx/8.5-alpine/Dockerfile',
            'docker/php/8.4/Dockerfile',
            'docker/php/8.5-alpine/Dockerfile',
            'docker/php/8.5/Dockerfile',
            'docker/toolbox/latest/Dockerfile',
        ], new FileReader(self::ROOT)->collectDockerfiles());
    }

    public function testCollectsNoDockerfileThroughASymbolicLink(): void
    {
        $repository = new TemporaryRepository();
        symlink('8.5', $repository->path . '/docker/php/8.6');

        try {
            self::assertNotContains('docker/php/8.6/Dockerfile', new FileReader($repository->path)->collectDockerfiles());
        } finally {
            $repository->remove();
        }
    }

    public function testCollectsOnlyTheDockerfilesOfTags(): void
    {
        $repository = new TemporaryRepository();
        // a tag whose Dockerfile was not generated yet, a Dockerfile in conf/ and one without a template
        $repository->write('docker/base/alpine-edge/Dockerfile.twig', '');
        $repository->write('docker/base/alpine/conf/Dockerfile', '');
        $repository->write('docker/toolbox/edge/Dockerfile', '');

        try {
            self::assertSame(
                new FileReader(self::ROOT)->collectDockerfiles(),
                new FileReader($repository->path)->collectDockerfiles(),
            );
        } finally {
            $repository->remove();
        }
    }

    public function testImageWithAnOutsideBaseImageIsBuiltAfterTheToolbox(): void
    {
        $node = $this->info('base/ubuntu-22.04');

        self::assertSame('webdevops/base:ubuntu-22.04', $node['id']);
        self::assertSame('webdevops/base:ubuntu-22.04', $node['name']);
        self::assertSame('base', $node['image']);
        self::assertSame('ubuntu-22.04', $node['tag']);
        self::assertSame('docker/base/ubuntu-22.04/Dockerfile', $node['file']);
        self::assertSame('ubuntu:22.04', $node['baseImage']);
        self::assertSame('webdevops/toolbox:latest', $node['parent']);
        self::assertSame(0, $node['imageParent'], 'the toolbox is no image to build on');
        self::assertNull($node['imageParentRef']);
    }

    public function testAutoLatestTagIsPublishedAsLatestToo(): void
    {
        self::assertSame(['webdevops/base:latest'], $this->info('base/ubuntu-22.04')['aliases']);
        self::assertSame([], $this->info('base/alpine')['aliases']);
    }

    public function testToolboxHasNoParent(): void
    {
        $node = $this->info('toolbox/latest');

        self::assertSame(0, $node['parent']);
        self::assertSame([], $node['imageDependencies']);
    }

    public function testDeprecatedNameIsPublishedForTheListedTags(): void
    {
        $node = $this->info('php/8.4');

        self::assertSame('webdevops/php:8.4', $node['id']);
        self::assertSame('webdevops/php:8.4', $node['name']);
        self::assertSame('php', $node['image']);
        self::assertSame(['webdevops/php-official:8.4'], $node['aliases']);
        self::assertSame([], $this->info('php-nginx/8.5-alpine')['aliases'], 'php-official names php only');
    }

    public function testDeprecatedNameGetsNoNewTags(): void
    {
        $repository = new TemporaryRepository();
        $repository->write('conf/console.yml', str_replace("tags: ['8.4', '8.5-alpine', '8.5']", "tags: ['8.4']", $repository->read('conf/console.yml')));

        try {
            self::assertSame([], new FileReader($repository->path)->getInfo('docker/php/8.5/Dockerfile')['aliases']);
            self::assertSame(['php-official' => ['image' => 'php', 'tags' => ['8.4']]], new FileReader($repository->path)->aliases());
        } finally {
            $repository->remove();
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function invalidAliases(): iterable
    {
        $tags = '"docker: aliases: php-official" in conf/console.yml: "tags" has to be a list of tags in quotes, e.g. [\'8.5\'].';
        yield 'tag without quotes' => ["      tags: [8.4, '8.5-alpine', '8.5']", $tags];
        yield 'empty tag' => ["      tags: ['', '8.5-alpine', '8.5']", $tags];
        yield 'no list' => ["      tags: '8.4'", $tags];
        yield 'tag listed twice' => ["      tags: ['8.4', '8.5', '8.4']", '"docker: aliases: php-official" in conf/console.yml: "tags" lists a tag twice.'];
        yield 'name of the image itself' => [
            "      tags: ['8.4']\n    php:\n      image: 'php'\n      tags: ['8.5']",
            '"docker: aliases: php" in conf/console.yml: the deprecated name has to differ from its image.',
        ];
        yield 'empty name' => [
            "      tags: ['8.4']\n    '':\n      image: 'php'\n      tags: ['8.5']",
            '"docker: aliases: " in conf/console.yml: the deprecated name has to be an image name in lower case, e.g. php-official.',
        ];
        yield 'name in upper case' => [
            "      tags: ['8.4']\n    PHP-Official:\n      image: 'php'\n      tags: ['8.5']",
            '"docker: aliases: PHP-Official" in conf/console.yml: the deprecated name has to be an image name in lower case, e.g. php-official.',
        ];
        yield 'image with its namespace' => [
            "      tags: ['8.4']\n    php-legacy:\n      image: 'webdevops/php'\n      tags: ['8.5']",
            '"docker: aliases: php-legacy" in conf/console.yml: "image" has to name the image in lower case, e.g. php.',
        ];
    }

    #[DataProvider('invalidAliases')]
    public function testInvalidAliasListIsAnError(string $line, string $message): void
    {
        $repository = new TemporaryRepository();
        $repository->write('conf/console.yml', str_replace("      tags: ['8.4', '8.5-alpine', '8.5']", $line, $repository->read('conf/console.yml')));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
            new FileReader($repository->path)->getInfo('docker/php/8.5/Dockerfile');
        } finally {
            $repository->remove();
        }
    }

    public function testAliasesThatAreNoMapAreAnError(): void
    {
        $repository = new TemporaryRepository();
        $repository->write('conf/console.yml', str_replace("  aliases:\n", "  aliases: 'php-official'\n  unused:\n", $repository->read('conf/console.yml')));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('"docker: aliases" in conf/console.yml has to map each deprecated name to the image it names and its tags, e.g. php-official: {image: php, tags: [\'8.5\']}.');
            new FileReader($repository->path)->aliases();
        } finally {
            $repository->remove();
        }
    }

    public function testInternalParentImage(): void
    {
        $node = $this->info('php-nginx/8.5-alpine');

        self::assertSame('webdevops/php:8.5-alpine', $node['parent']);
        self::assertSame('webdevops/php:8.5-alpine', $node['imageParent']);
        self::assertSame('webdevops/php:8.5-alpine', $node['imageParentRef']);
        self::assertSame('webdevops/php:8.5-alpine', $node['baseImage']);
    }

    public function testLatestParentResolvesToTheAutoLatestTag(): void
    {
        $node = $this->info('base-app/ubuntu-22.04');

        self::assertSame('webdevops/base:ubuntu-22.04', $node['parent']);
        self::assertSame('webdevops/base:ubuntu-22.04', $node['imageParent']);
        self::assertSame('webdevops/base:latest', $node['imageParentRef'], 'the reference as written in the FROM line');
    }

    public function testCopyFromAnInternalImageIsADependency(): void
    {
        self::assertSame(
            ['webdevops/toolbox' => 'webdevops/toolbox:latest'],
            $this->info('base/ubuntu-22.04')['imageDependencies'],
            'an untagged reference means latest, and the toolbox has a latest directory',
        );
        self::assertSame(
            ['webdevops/toolbox:latest' => 'webdevops/toolbox:latest'],
            $this->info('base/alpine')['imageDependencies'],
        );
    }

    public function testAutoLatestTag(): void
    {
        self::assertSame('ubuntu-22.04', new FileReader(self::ROOT)->autoLatestTag());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidAutoLatestTags(): iterable
    {
        yield 'number without quotes' => ['  autoLatestTag: 22.10'];
        yield 'empty' => ["  autoLatestTag: ''"];
        yield 'missing' => [''];
    }

    #[DataProvider('invalidAutoLatestTags')]
    public function testAutoLatestTagHasToBeAQuotedTag(string $line): void
    {
        $repository = new TemporaryRepository();
        $repository->write('conf/console.yml', str_replace("  autoLatestTag: 'ubuntu-22.04'", $line, $repository->read('conf/console.yml')));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('"docker: autoLatestTag" in conf/console.yml has to be a tag in quotes, e.g. \'ubuntu-22.04\', or ~ for none.');
            new FileReader($repository->path)->getInfo('docker/base/ubuntu-22.04/Dockerfile');
        } finally {
            $repository->remove();
        }
    }

    public function testNoTagIsPublishedAsLatestWithoutAutoLatestTag(): void
    {
        $repository = new TemporaryRepository();
        $repository->write('conf/console.yml', str_replace("  autoLatestTag: 'ubuntu-22.04'", '  autoLatestTag: ~', $repository->read('conf/console.yml')));

        try {
            $fileReader = new FileReader($repository->path);
            self::assertNull($fileReader->autoLatestTag());
            self::assertSame([], $fileReader->getInfo('docker/base/ubuntu-22.04/Dockerfile')['aliases']);
            self::assertSame('webdevops/base:latest', $fileReader->getInfo('docker/base-app/ubuntu-22.04/Dockerfile')['parent'], 'base has no latest any more');
        } finally {
            $repository->remove();
        }
    }

    public function testDockerfileWithoutHeaderIsAnError(): void
    {
        $repository = new TemporaryRepository();
        $repository->write('docker/broken/latest/Dockerfile', "FROM alpine:3.22\n");

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('docker/broken/latest/Dockerfile has no "# Dockerfile for webdevops/<image>:<tag>" header.');
            new FileReader($repository->path)->getInfo('docker/broken/latest/Dockerfile');
        } finally {
            $repository->remove();
        }
    }

    public function testMissingDockerfileIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read docker/missing/latest/Dockerfile.');
        new FileReader(self::ROOT)->getInfo('docker/missing/latest/Dockerfile');
    }

    /**
     * @return array<string, mixed>
     */
    private function info(string $directory): array
    {
        return new FileReader(self::ROOT)->getInfo('docker/' . $directory . '/Dockerfile');
    }
}
