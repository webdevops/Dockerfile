<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\TestImageCommand;
use Webdevops\Build\Tests\TemporaryRepository;

use function array_diff_key;
use function array_flip;

use const PHP_BINARY;
use const SIGINT;
use const SIGTERM;

#[CoversClass(TestImageCommand::class)]
final class TestImageCommandTest extends TestCase
{
    /**
     * @var list<array{list<string>, array<string, string>}>
     */
    private array $runs = [];

    /**
     * @var list<string>
     */
    private array $removed = [];

    public function testRunsPhpUnitWithTheTestClassAndTheSettings(): void
    {
        self::assertSame(3, $this->tester(3)->execute(['image' => 'webdevops/base:alpine']), 'the exit code of PHPUnit');

        $root = TemporaryRepository::FIXTURE;
        [$command, $environment] = $this->runs[0];
        self::assertSame(
            [PHP_BINARY, $root . '/ci/vendor/bin/phpunit', '--configuration', $root . '/ci/phpunit.xml.dist', $root . '/ci/image-tests/Images/BaseTest.php'],
            $command,
        );
        self::assertSame([
            'IMAGE_TEST_NAME' => 'webdevops/base:alpine',
            'IMAGE_TEST_REFERENCE' => 'webdevops/base:alpine',
            'IMAGE_TEST_OS_FAMILY' => 'alpine',
            'IMAGE_TEST_OS_VERSION' => '',
        ], array_diff_key($environment, array_flip(['IMAGE_TEST_RUN'])));
        self::assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $environment['IMAGE_TEST_RUN']);
    }

    public function testRemovesTheContainersOfTheRunAfterPhpUnit(): void
    {
        $this->tester(1)->execute(['image' => 'webdevops/base:alpine']);

        self::assertSame([$this->runs[0][1]['IMAGE_TEST_RUN']], $this->removed);
    }

    public function testRemovesTheContainersWhenPhpUnitFails(): void
    {
        $tester = new CommandTester(new TestImageCommand(
            TemporaryRepository::FIXTURE,
            static fn (): int => throw new RuntimeException('PHPUnit crashed'),
            function (string $run): void {
                $this->removed[] = $run;
            },
        ));

        try {
            $tester->execute(['image' => 'webdevops/base:alpine']);
            self::fail('The exception of the runner should pass through.');
        } catch (RuntimeException) {
            self::assertCount(1, $this->removed);
        }
    }

    public function testInterruptionRemovesTheContainersOfTheRun(): void
    {
        $testImage = null;
        $testImage = new TestImageCommand(
            TemporaryRepository::FIXTURE,
            function (array $command, array $environment, OutputInterface $output) use (&$testImage): int {
                // the signal arrives while PHPUnit runs
                self::assertSame(128 + SIGINT, $testImage?->handleSignal(SIGINT));
                self::assertSame([$environment['IMAGE_TEST_RUN']], $this->removed);
                return 130;
            },
            function (string $run): void {
                $this->removed[] = $run;
            },
        );

        new CommandTester($testImage)->execute(['image' => 'webdevops/base:alpine']);

        self::assertCount(1, $this->removed, 'removed once, not again after PHPUnit');
        self::assertSame([SIGINT, SIGTERM], $testImage->getSubscribedSignals());
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
        ], array_diff_key($this->runs[0][1], array_flip(['IMAGE_TEST_RUN'])));
    }

    public function testOfficialPhpImageRunsTheTestsOfPhp(): void
    {
        $this->tester(0)->execute(['image' => 'webdevops/php-official:8.4']);

        self::assertStringEndsWith('/ci/image-tests/Images/PhpTest.php', $this->runs[0][0][4]);
        self::assertSame('webdevops/php-official:8.4', $this->runs[0][1]['IMAGE_TEST_NAME']);
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
            self::assertSame([], $this->removed, 'no container to remove');
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
            function (string $run): void {
                $this->removed[] = $run;
            },
        ));
    }
}
