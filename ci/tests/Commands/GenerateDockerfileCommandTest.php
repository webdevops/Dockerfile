<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\GenerateDockerfileCommand;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(GenerateDockerfileCommand::class)]
final class GenerateDockerfileCommandTest extends TestCase
{
    public function testRendersTheDockerfilesAndListsThem(): void
    {
        $repository = new TemporaryRepository();
        try {
            $repository->write('docker/toolbox/latest/Dockerfile', '');
            $tester = new CommandTester(new GenerateDockerfileCommand($repository->path));

            self::assertSame(Command::SUCCESS, $tester->execute([]));
            self::assertStringContainsString("webdevops/toolbox:latest\n", $tester->getDisplay());
            self::assertStringContainsString("FROM alpine:3.22\n\nRUN mkdir /baselayout\n", $repository->read('docker/toolbox/latest/Dockerfile'));
        } finally {
            $repository->remove();
        }
    }
}
