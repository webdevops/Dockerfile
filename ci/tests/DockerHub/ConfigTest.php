<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\DockerHub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\DockerHub\Config;
use Webdevops\Build\DockerHub\Repository;
use Webdevops\Build\Tests\TemporaryRepository;

use function array_map;
use function str_repeat;
use function str_replace;

#[CoversClass(Config::class)]
#[CoversClass(Repository::class)]
final class ConfigTest extends TestCase
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

    public function testRepositoriesAreSortedAndCarryTheShortDescription(): void
    {
        $repositories = new Config($this->repository->path)->repositories();

        self::assertSame(
            ['base', 'base-app', 'hhvm', 'nginx', 'php', 'php-nginx', 'php-nginx-dev', 'php-official', 'toolbox'],
            array_map(static fn (Repository $repository): string => $repository->name, $repositories),
        );
        $byName = [];
        foreach ($repositories as $repository) {
            $byName[$repository->name] = $repository;
        }

        self::assertSame('No longer built or updated. Use nginx:alpine instead.', $byName['nginx']->shortDescription);
        self::assertFalse($byName['nginx']->built);
        self::assertSame('No longer built or updated.', $byName['hhvm']->shortDescription);
        self::assertSame('PHP-FPM on the official PHP image', $byName['php']->shortDescription);
        self::assertTrue($byName['php']->built);
        self::assertSame('docs/dockerhub/php.md', $byName['php']->pagePath());
    }

    public function testDefaults(): void
    {
        $config = new Config($this->repository->path);

        self::assertNull($config->built()['base']['example']);
        self::assertFalse($config->built()['base']['development']);
        self::assertNull($config->built()['base']['note']);
        self::assertTrue($config->built()['php-nginx-dev']['development']);
        self::assertSame(['lang' => 'yaml', 'code' => "services:\n  php:\n    image: {image}\n", 'tag' => null], $config->built()['php']['example']);
        self::assertNull($config->discontinued()['hhvm']['replacement']);
        self::assertSame(['bookworm' => 'Debian 12', 'trixie' => 'Debian 13'], $config->releases());
    }

    public function testShortDescriptionLongerThanTheLimitIsAnError(): void
    {
        $this->replace("short: 'Base image with entrypoint and supervisord'", "short: '" . str_repeat('a', 101) . "'");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The short description of webdevops/base has 101 bytes; Docker Hub takes at most 100.');
        self::assertNotEmpty(new Config($this->repository->path)->repositories());
    }

    public function testGeneratedShortDescriptionIsCheckedToo(): void
    {
        $this->replace("image: 'nginx:alpine'", "image: '" . str_repeat('a', 60) . "'");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The short description of webdevops/nginx has 101 bytes; Docker Hub takes at most 100.');
        self::assertNotEmpty(new Config($this->repository->path)->repositories());
    }

    public function testRepositoryInBothListsIsAnError(): void
    {
        $this->replace("  hhvm:\n", "  base:\n    lastBuilt: '2020-04'\n  hhvm:\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('webdevops/base is listed under both "built" and "discontinued" in conf/dockerhub.yml.');
        self::assertNotEmpty(new Config($this->repository->path)->repositories());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidMonths(): iterable
    {
        yield 'year only' => ["'2020'"];
        yield 'number' => ['2020'];
        yield 'month name' => ["'Apr 2020'"];
        yield 'month 13' => ["'2020-13'"];
    }

    #[DataProvider('invalidMonths')]
    public function testLastBuiltHasToBeAMonth(string $value): void
    {
        $this->replace("lastBuilt: '2020-04'", 'lastBuilt: ' . $value);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("lastBuilt of webdevops/hhvm in conf/dockerhub.yml has to be a month in quotes, e.g. '2026-11'.");
        self::assertNotEmpty(new Config($this->repository->path)->repositories());
    }

    public function testMissingKeyIsAnError(): void
    {
        $this->replace("    intro: 'Entrypoint and supervisord, see [Services](docs/customization.md#services).'\n", '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('webdevops/base in conf/dockerhub.yml needs "intro".');
        self::assertNotEmpty(new Config($this->repository->path)->repositories());
    }

    private function replace(string $search, string $replace): void
    {
        $content = $this->repository->read(Config::FILE);
        self::assertStringContainsString($search, $content);
        $this->repository->write(Config::FILE, str_replace($search, $replace, $content));
    }
}
