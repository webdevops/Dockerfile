<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\GenerateCommand;
use Webdevops\Build\Commands\GenerateDockerfileCommand;
use Webdevops\Build\Commands\GenerateProvisionCommand;
use Webdevops\Build\Commands\GenerateGithubCiCommand;
use Webdevops\Build\Commands\GenerateReadmeBannerCommand;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(GenerateCommand::class)]
final class GenerateCommandTest extends TestCase
{
    private TemporaryRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TemporaryRepository();
        $this->repository->write('.github/workflows/build.yaml', '');
    }

    protected function tearDown(): void
    {
        $this->repository->remove();
    }

    public function testWritesEveryGeneratedFile(): void
    {
        $this->repository->write('docker/toolbox/latest/Dockerfile', '');

        $tester = new CommandTester($this->application()->find('generate'));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('RUN mkdir /baselayout', $this->repository->read('docker/toolbox/latest/Dockerfile'));
        self::assertSame("alpine\n", $this->repository->read('docker/base/alpine/conf/etc/os'));
        self::assertStringContainsString('toolbox_latest:', $this->repository->read('.github/workflows/build.yaml'));
        self::assertStringContainsString('<svg', $this->repository->read('docs/assets/banner-light.svg'));
    }

    public function testRunsTheGeneratorsInOrder(): void
    {
        $tester = new CommandTester($this->application()->find('generate'));
        $tester->execute([]);

        self::assertMatchesRegularExpression(
            '/^generate:dockerfile\n(webdevops\/.*\n)+generate:provision\n(.* => .*\n)+generate:github-ci\n(.*\n)+generate:readme-banner\nWrote /',
            $tester->getDisplay(),
        );
    }

    public function testStopsAtTheFirstFailure(): void
    {
        $application = $this->application();
        $application->add(new #[AsCommand(name: 'generate:provision')] class () extends Command {
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return Command::FAILURE;
            }
        });
        $tester = new CommandTester($application->find('generate'));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringNotContainsString('generate:github-ci', $tester->getDisplay());
    }

    public function testNeedsTheApplication(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('generate runs the other commands and needs the console application.');
        new CommandTester(new GenerateCommand())->execute([]);
    }

    private function application(): Application
    {
        $application = new Application();
        $application->add(new GenerateCommand());
        $application->add(new GenerateDockerfileCommand($this->repository->path));
        $application->add(new GenerateProvisionCommand($this->repository->path));
        $application->add(new GenerateGithubCiCommand($this->repository->path));
        $application->add(new GenerateReadmeBannerCommand($this->repository->path));
        return $application;
    }
}
