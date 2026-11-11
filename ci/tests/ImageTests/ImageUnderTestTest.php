<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ImageTests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ImageTests\ImageUnderTest;

use function putenv;

#[CoversClass(ImageUnderTest::class)]
final class ImageUnderTestTest extends TestCase
{
    private const array VARIABLES = ['NAME', 'REFERENCE', 'OS_FAMILY', 'OS_VERSION', 'PHP_OFFICIAL', 'RUN'];

    protected function tearDown(): void
    {
        foreach (self::VARIABLES as $variable) {
            putenv('IMAGE_TEST_' . $variable);
        }
    }

    public function testSettingsFromTheEnvironment(): void
    {
        putenv('IMAGE_TEST_NAME=webdevops/php-nginx:8.3-alpine');
        putenv('IMAGE_TEST_REFERENCE=local/php-nginx');
        putenv('IMAGE_TEST_OS_FAMILY=alpine');
        putenv('IMAGE_TEST_OS_VERSION=3');
        putenv('IMAGE_TEST_PHP_OFFICIAL=1');
        putenv('IMAGE_TEST_RUN=0123456789abcdef');

        $image = ImageUnderTest::fromEnvironment();

        self::assertSame('0123456789abcdef', $image->run);

        self::assertSame('local/php-nginx', $image->reference);
        self::assertSame('8.3-alpine', $image->tag());
        self::assertSame('8.3', $image->phpMinorVersion());
        self::assertTrue($image->isAlpine());
        self::assertFalse($image->isDebianOrUbuntu());
        self::assertTrue($image->phpOfficial);
        self::assertTrue($image->hasImap());
    }

    public function testReferenceDefaultsToTheName(): void
    {
        putenv('IMAGE_TEST_NAME=webdevops/php:8.5');
        putenv('IMAGE_TEST_OS_FAMILY=debian');

        $image = ImageUnderTest::fromEnvironment();

        self::assertSame('webdevops/php:8.5', $image->reference);
        self::assertSame('', $image->run, 'outside ci/console test:image');
        self::assertTrue($image->isDebianOrUbuntu());
        self::assertFalse($image->phpOfficial);
        self::assertFalse($image->hasImap(), 'PHP 8.4 and later have no imap');
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ci/console test:image');
        ImageUnderTest::fromEnvironment();
    }

    public function testImageWithoutPhpVersion(): void
    {
        $image = new ImageUnderTest('webdevops/base:ubuntu-22.04', 'webdevops/base:ubuntu-22.04', 'ubuntu', '22.04', false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('webdevops/base:ubuntu-22.04 has no PHP version in its tag.');
        $image->phpMinorVersion();
    }
}
