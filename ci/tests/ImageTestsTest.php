<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ImageTests;

#[CoversClass(ImageTests::class)]
final class ImageTestsTest extends TestCase
{
    public function testTestFileOfAnImageIsItsNameInStudlyCaps(): void
    {
        $imageTests = new ImageTests(TemporaryRepository::FIXTURE);

        self::assertSame('ci/image-tests/Images/BaseTest.php', $imageTests->testFile('base'));
        self::assertNull($imageTests->testFile('php-nginx'), 'the fixture has no PhpNginxTest.php');
    }

    public function testEveryImageWithTestsInTheRepositoryHasAClass(): void
    {
        $imageTests = new ImageTests(__DIR__ . '/../..');

        self::assertSame('ci/image-tests/Images/PhpNginxDevTest.php', $imageTests->testFile('php-nginx-dev'));
        self::assertSame('ci/image-tests/Images/BaseAppTest.php', $imageTests->testFile('base-app'));
        self::assertNull($imageTests->testFile('toolbox'));
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function settings(): iterable
    {
        yield 'family and version from the tag' => ['webdevops/base:ubuntu-22.04', ['OS_FAMILY' => 'ubuntu', 'OS_VERSION' => '22.04']];
        yield 'family without version' => ['webdevops/base:alpine', ['OS_FAMILY' => 'alpine', 'OS_VERSION' => '']];
        yield 'defaults' => ['webdevops/toolbox:latest', ['OS_FAMILY' => 'ubuntu', 'OS_VERSION' => '22.04']];
        yield 'first matching image settings, not the second' => [
            'webdevops/php-official:8.5-alpine',
            ['OS_FAMILY' => 'alpine', 'OS_VERSION' => '3', 'PHP_OFFICIAL' => '1'],
        ];
        yield 'php as published' => ['webdevops/php:8.4', ['OS_FAMILY' => 'debian', 'OS_VERSION' => '12', 'PHP_OFFICIAL' => '1']];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('settings')]
    public function testSettings(string $name, array $expected): void
    {
        self::assertSame($expected, new ImageTests(TemporaryRepository::FIXTURE)->settings($name));
    }

    public function testMissingSettingsAreAnError(): void
    {
        $repository = new TemporaryRepository();
        try {
            $repository->write('conf/console.yml', "docker:\n  autoLatestTag: 'ubuntu-22.04'\n");

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('conf/console.yml needs "dockerTest: configuration"');
            new ImageTests($repository->path)->settings('webdevops/base:alpine');
        } finally {
            $repository->remove();
        }
    }
}
