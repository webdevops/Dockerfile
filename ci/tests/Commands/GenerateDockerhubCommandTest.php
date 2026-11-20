<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\GenerateDockerhubCommand;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(GenerateDockerhubCommand::class)]
final class GenerateDockerhubCommandTest extends TestCase
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

    public function testWritesThePages(): void
    {
        $this->repository->write('docs/dockerhub/old.md', "# webdevops/old\n");
        $tester = new CommandTester(new GenerateDockerhubCommand($this->repository->path));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringStartsWith("Wrote docs/dockerhub/base.md\nWrote docs/dockerhub/base-app.md\n", $tester->getDisplay());
        self::assertStringEndsWith("Wrote docs/dockerhub/toolbox.md\nRemoved docs/dockerhub/old.md\n", $tester->getDisplay());
        self::assertStringContainsString('# webdevops/php', $this->repository->read('docs/dockerhub/php.md'));
    }
}
