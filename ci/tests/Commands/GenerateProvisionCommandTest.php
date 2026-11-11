<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\GenerateProvisionCommand;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(GenerateProvisionCommand::class)]
final class GenerateProvisionCommandTest extends TestCase
{
    public function testProvisionsTheImagesAndListsTheSources(): void
    {
        $repository = new TemporaryRepository();
        try {
            $tester = new CommandTester(new GenerateProvisionCommand($repository->path));

            self::assertSame(Command::SUCCESS, $tester->execute([]));
            self::assertStringContainsString("php/official-alpine => webdevops/php-official:*-alpine\n", $tester->getDisplay());
            self::assertSame("alpine\n", $repository->read('docker/base/alpine/conf/etc/os'));
        } finally {
            $repository->remove();
        }
    }
}
