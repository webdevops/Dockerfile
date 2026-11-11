<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests;

use LogicException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\ImageTests\Docker\CommandResult;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\Docker\FileInfo;
use Webdevops\Build\ImageTests;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;

use function array_any;
use function array_filter;
use function array_map;
use function array_values;
use function escapeshellarg;
use function explode;
use function implode;
use function sleep;
use function sprintf;
use function str_contains;
use function str_split;
use function strtolower;
use function trim;

/**
 * Base class of the tests of one image. All tests of a class run against one container of the image, which starts
 * with the image's own command, e.g. supervisord with its services. The files of Fixtures/ are copied into its root
 * directory first, so /app holds the pages that the web server tests request.
 */
abstract class ImageTestCase extends TestCase
{
    /**
     * How often and how long apart a check of a service is tried, while the service starts.
     */
    protected const int ATTEMPTS = 20;

    protected const int WAIT_SECONDS = 3;

    private static ?ImageUnderTest $image = null;

    private static ?Container $container = null;

    /**
     * Whether a check of this class already waited for all its attempts. The services then had their time to start,
     * so later checks try only once, and a broken image fails in minutes, not in hours.
     */
    private static bool $waitedForAllAttempts = false;

    public static function setUpBeforeClass(): void
    {
        self::$image = ImageUnderTest::fromEnvironment();
        self::$waitedForAllAttempts = false;
        self::$container = Container::start(
            self::$image->reference,
            __DIR__ . '/Fixtures',
            self::$image->run === '' ? [] : [ImageTests::CONTAINER_LABEL => self::$image->run],
        );
    }

    public static function tearDownAfterClass(): void
    {
        self::$container?->remove();
        self::$container = null;
    }

    /**
     * Data provider cases of single strings, each named after itself, e.g. ['/usr' => ['/usr']].
     *
     * @param list<string> $values
     * @return array<string, array{string}>
     */
    protected static function cases(array $values): array
    {
        $cases = [];
        foreach ($values as $value) {
            $cases[$value] = [$value];
        }

        return $cases;
    }

    /**
     * The schemes of the requests to the web server, http and https.
     *
     * @return iterable<string, array{string}>
     */
    public static function webSchemes(): iterable
    {
        yield 'http' => ['http'];
        yield 'https' => ['https'];
    }

    protected static function image(): ImageUnderTest
    {
        return self::$image ?? throw new LogicException('The image is set in setUpBeforeClass().');
    }

    protected static function container(): Container
    {
        return self::$container ?? throw new LogicException('The container is started in setUpBeforeClass().');
    }

    protected function execute(string $command): CommandResult
    {
        return self::container()->exec($command);
    }

    /**
     * Runs $command and asserts that it exits with 0.
     */
    protected function assertCommandSucceeds(string $command): CommandResult
    {
        $result = $this->execute($command);
        self::assertSame(0, $result->exitCode, $result->describe());
        return $result;
    }

    /**
     * Runs $command, asserts that it exits with 0 and returns its standard output.
     */
    protected function stdout(string $command): string
    {
        return $this->assertCommandSucceeds($command)->stdout;
    }

    protected function file(string $path): FileInfo
    {
        return FileInfo::read(self::container(), $path);
    }

    /**
     * Retries $check until it passes, while a service starts, and fails with its last failure. Once a check of the
     * class has used all its attempts, checks try only once.
     */
    protected function eventually(callable $check, int $attempts = self::ATTEMPTS, int $waitSeconds = self::WAIT_SECONDS): void
    {
        if (self::$waitedForAllAttempts) {
            $attempts = 1;
        }

        $attempt = 0;
        while (true) {
            ++$attempt;
            try {
                $check();
                return;
            } catch (AssertionFailedError $failure) {
                if ($attempt >= $attempts) {
                    self::$waitedForAllAttempts = true;
                    throw $failure;
                }

                sleep($waitSeconds);
            }
        }
    }

    /**
     * Asserts which of r, w and x the mode of $file gives the owner, the group and others. $access maps each class to
     * the access it must have; a letter in upper case means the access must be missing. Letters that are not given
     * are not checked, e.g. ['owner' => 'rwx', 'group' => 'rX'].
     *
     * @param array<'owner'|'group'|'others', string> $access
     */
    protected function assertAccess(FileInfo $file, array $access): void
    {
        foreach ($access as $class => $letters) {
            foreach (str_split($letters) as $letter) {
                $wanted = strtolower($letter);
                $expected = $letter === $wanted;
                self::assertSame($expected, $file->permits($class, $wanted), sprintf(
                    '%s should %sbe %s by %s (mode %o)',
                    $file->path,
                    $expected ? '' : 'not ',
                    ['r' => 'readable', 'w' => 'writable', 'x' => 'executable'][$wanted] ?? $wanted,
                    $class,
                    $file->mode,
                ));
            }
        }
    }

    protected function assertOwnedByRoot(FileInfo $file): void
    {
        self::assertTrue($file->exists, $file->path . ' should exist');
        self::assertSame('root', $file->owner, $file->path . ' should be owned by root');
        self::assertSame('root', $file->group, $file->path . ' should be grouped into root');
    }

    /**
     * A directory of root that everybody can read and enter, and only root can change.
     */
    protected function assertRootDirectory(string $path): void
    {
        $file = $this->file($path);
        self::assertTrue($file->isDirectory, $path . ' should be a directory');
        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, ['owner' => 'rwx', 'group' => 'rWx', 'others' => 'rWx']);
    }

    /**
     * A file of root that everybody can read, only root can change, and nobody can execute.
     */
    protected function assertRootFile(string $path): void
    {
        $file = $this->file($path);
        self::assertTrue($file->isFile, $path . ' should be a file');
        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, ['owner' => 'rwX', 'group' => 'rWX', 'others' => 'rWX']);
    }

    /**
     * A program of root that everybody can read and execute, and only root can change.
     */
    protected function assertRootScript(string $path): void
    {
        $file = $this->file($path);
        self::assertTrue($file->isFile, $path . ' should be a file');
        self::assertTrue($file->isExecutable, $path . ' should be executable');
        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, ['owner' => 'rwx', 'group' => 'rWx', 'others' => 'rWx']);
    }

    /**
     * A file or directory that the root user and group may read, but other users may not, and only root can change,
     * e.g. a TLS key.
     */
    protected function assertPrivateToRoot(string $path, bool $directory): void
    {
        $file = $this->file($path);
        if ($directory) {
            self::assertTrue($file->isDirectory, $path . ' should be a directory');
        } else {
            self::assertTrue($file->isFile, $path . ' should be a file');
        }

        $this->assertOwnedByRoot($file);
        $this->assertAccess($file, $directory
            ? ['owner' => 'rwx', 'group' => 'rWx', 'others' => 'RWX']
            : ['owner' => 'rwX', 'group' => 'rWX', 'others' => 'RWX']);
        $this->assertUserCannot('application', 'r', $path);
        $this->assertUserCannot('application', 'w', $path);
        if ($directory) {
            $this->assertUserCannot('application', 'x', $path);
        }
    }

    /**
     * Asserts that $user cannot r, w or x $path, tried with su as that user. Images without the user pass.
     */
    protected function assertUserCannot(string $user, string $access, string $path): void
    {
        if ($this->execute('id ' . escapeshellarg($user))->exitCode !== 0) {
            return;
        }

        $this->assertCommandSucceeds(sprintf('su -s /bin/sh %s -c true', escapeshellarg($user)));
        $result = $this->execute(sprintf('su -s /bin/sh %s -c %s', escapeshellarg($user), escapeshellarg('test -' . $access . ' ' . escapeshellarg($path))));
        self::assertNotSame(0, $result->exitCode, sprintf('%s should not be able to %s %s', $user, $access, $path));
    }

    /**
     * A package of the distribution is installed, checked with apk or dpkg.
     */
    protected function assertPackageInstalled(string $package): void
    {
        if (self::image()->isAlpine()) {
            $this->assertCommandSucceeds('apk info -qe ' . escapeshellarg($package));
            return;
        }

        self::assertMatchesRegularExpression(
            '/^(install|hold) ok installed$/',
            $this->stdout("dpkg-query -f '\${Status}' -W " . escapeshellarg($package)),
            $package . ' should be installed',
        );
    }

    /**
     * A TCP socket listens on $port, on one of $addresses if given, e.g. 0.0.0.0 or ::. Retried while the service
     * starts.
     */
    protected function assertListening(int $port, string ...$addresses): void
    {
        $this->eventually(function () use ($port, $addresses): void {
            $sockets = ListeningSockets::read(self::container());
            self::assertTrue(array_any($addresses ?: [null], static fn (?string $address): bool => $sockets->isListening($port, $address)), sprintf(
                'Nothing listens on %s; listening: %s',
                implode(' or ', array_map(static fn (string $address): string => (str_contains($address, ':') ? '[' . $address . ']' : $address) . ':' . $port, $addresses ?: ['*'])),
                $sockets->describe(),
            ));
        });
    }

    /**
     * No TCP socket listens on $port on any of $addresses.
     */
    protected function assertNotListening(int $port, string ...$addresses): void
    {
        $sockets = ListeningSockets::read(self::container());
        foreach ($addresses as $address) {
            self::assertFalse($sockets->isListening($port, $address), sprintf('Something listens on %s:%d; listening: %s', $address, $port, $sockets->describe()));
        }
    }

    /**
     * "service <name> check" of the image reports the service as running, and "service <name> pid" names its process.
     * The check is retried while the service starts.
     */
    protected function assertServiceRunning(string $service): void
    {
        $this->eventually(function () use ($service): void {
            $result = $this->execute('service ' . escapeshellarg($service) . ' check');
            self::assertStringContainsString('ok', $result->stdout, $result->describe());
            self::assertSame(0, $result->exitCode, $result->describe());
        });

        $pid = $this->stdout('service ' . escapeshellarg($service) . " pid | tr -d '\\n'");
        self::assertMatchesRegularExpression('/^[0-9]+$/', $pid, $service . ' should have a process id');
        self::assertNotSame('0', $pid, $service . ' should have a process id');
    }

    /**
     * Requests $url with curl in the container, retried while the web server and PHP-FPM start, and returns the body
     * of the first successful response. $check asserts the body and is retried with the request.
     *
     * @param (callable(string): void)|null $check
     */
    protected function request(string $url, string $curlOptions = '', ?callable $check = null): string
    {
        $body = '';
        $this->eventually(function () use ($url, $curlOptions, $check, &$body): void {
            $result = $this->execute('curl --insecure --silent --show-error --fail ' . $curlOptions . ' ' . escapeshellarg($url));
            self::assertSame(0, $result->exitCode, $result->describe());
            if ($check !== null) {
                $check($result->stdout);
            }

            $body = $result->stdout;
        });

        return $body;
    }

    /**
     * Asserts that PHP printed no notice, warning or fatal error into $output.
     */
    protected function assertNoPhpErrors(string $output): void
    {
        foreach (['Notice', 'Warning', 'Fatal Error'] as $message) {
            self::assertStringNotContainsString($message, $output);
        }
    }

    /**
     * The lines of $output that are not empty, without surrounding white space, e.g. the module names of "php -m".
     *
     * @return list<string>
     */
    protected function lines(string $output): array
    {
        return array_values(array_filter(array_map(trim(...), explode("\n", $output)), static fn (string $line): bool => $line !== ''));
    }
}
