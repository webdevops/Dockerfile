<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webdevops\Build\Commands\GenerateReadmeBannerCommand;
use Webdevops\Build\Tests\TemporaryRepository;

#[CoversClass(GenerateReadmeBannerCommand::class)]
final class GenerateReadmeBannerCommandTest extends TestCase
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

    public function testRendersTheBannerFromTheDockerfiles(): void
    {
        $tester = new CommandTester(new GenerateReadmeBannerCommand($this->repository->path));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame("Wrote docs/assets/banner-light.svg\nWrote docs/assets/banner-dark.svg\n", $tester->getDisplay());
        self::assertSame(
            'Image tag anatomy using webdevops/php-nginx-dev:8.5-alpine as the example. '
            . 'Web server: php (PHP-FPM) or php-nginx (+ Nginx). Variant: no suffix for production, -dev for development. '
            . 'PHP version: 8.4 or 8.5. Base OS: no suffix for Debian (bookworm: 8.4; trixie: 8.5), -alpine for Alpine. '
            . 'Extends the official php images. linux/amd64 + linux/arm64.',
            (string)new SimpleXMLElement($this->repository->read('docs/assets/banner-dark.svg'))->desc,
        );
    }
}
