<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\TestImageCommand;
use Webdevops\Build\Tests\TemporaryRepository;

use const PHP_BINARY;

#[CoversClass(TestImageCommand::class)]
final class TestImageCommandTest extends TestCase
{
    /**
     * @var list<array{list<string>, array<string, string>}>
     */
    private array $runs = [];

    public function testRunsPhpUnitWithTheTestClassAndTheSettings(): void
    {
        $tester = $this->tester(3);

        self::assertSame(3, $tester->execute(['image' => 'webdevops/base:alpine']), 'the exit code of PHPUnit');
        $root = TemporaryRepository::FIXTURE;
        self::assertSame([[
            [PHP_BINARY, $root . '/ci/vendor/bin/phpunit', '--configuration', $root . '/ci/phpunit.xml.dist', $root . '/ci/image-tests/Images/BaseTest.php'],
            [
                'IMAGE_TEST_NAME' => 'webdevops/base:alpine',
                'IMAGE_TEST_REFERENCE' => 'webdevops/base:alpine',
                'IMAGE_TEST_OS_FAMILY' => 'alpine',
                'IMAGE_TEST_OS_VERSION' => '',
            ],
        ]], $this->runs);
    }

    public function testReferenceNamesTheImageToStart(): void
    {
        $this->tester(0)->execute(['image' => 'webdevops/php:8.4', '--reference' => 'ghcr.io/webdevops/php:sha-1-amd64-8.4']);

        self::assertSame([
            'IMAGE_TEST_NAME' => 'webdevops/php:8.4',
            'IMAGE_TEST_REFERENCE' => 'ghcr.io/webdevops/php:sha-1-amd64-8.4',
            'IMAGE_TEST_OS_FAMILY' => 'debian',
            'IMAGE_TEST_OS_VERSION' => '12',
            'IMAGE_TEST_PHP_OFFICIAL' => '1',
        ], $this->runs[0][1]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidImages(): iterable
    {
        yield 'no namespace' => ['base:alpine', 'Name the image as webdevops/<image>:<tag>'];
        yield 'no tag' => ['webdevops/base', 'Name the image as webdevops/<image>:<tag>'];
        yield 'no tests' => ['webdevops/php-nginx:8.5', 'webdevops/php-nginx has no image tests in ci/image-tests/Images.'];
    }

    #[DataProvider('invalidImages')]
    public function testRejectsImagesWithoutTests(string $image, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        try {
            $this->tester(0)->execute(['image' => $image]);
        } finally {
            self::assertSame([], $this->runs, 'PHPUnit does not run');
        }
    }

    private function tester(int $exitCode): CommandTester
    {
        return new CommandTester(new TestImageCommand(
            TemporaryRepository::FIXTURE,
            function (array $command, array $environment, OutputInterface $output) use ($exitCode): int {
                $this->runs[] = [$command, $environment];
                return $exitCode;
            },
        ));
    }
}
