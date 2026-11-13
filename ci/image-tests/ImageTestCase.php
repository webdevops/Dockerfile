<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webdevops\Build\ImageTests\Docker\CommandResult;
use Webdevops\Build\ImageTests\Docker\Container;
use Webdevops\Build\ImageTests\Docker\FileInfo;
use Webdevops\Build\ImageTests;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;

use function array_any;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function escapeshellarg;
use function explode;
use function implode;
use function sleep;
use function sprintf;
use function str_contains;
use function str_split;
use function strtolower;
use function trim;
use function preg_grep;

/**
 * Base class of the tests of one image. Most tests of a class run against one container of the image, which starts
 * with the image's own command, e.g. supervisord with its services. The files of Fixtures/ are copied into its root
 * directory first, so /app holds the pages that the web server tests request.
 *
 * Tests of environment variables, commands and hooks start further containers of the image with startContainer(),
 * startTestContainer() and runContainer(). They also get the files of Hooks/: scripts that mark that a hook ran, and
 * configuration that single tests switch on. The container of the class does not, so that the hook directories there
 * are the image's own.
 */
abstract class ImageTestCase extends TestCase
{
    /**
     * How often and how long apart a check of a service is tried, while the service starts.
     */
    protected const int ATTEMPTS = 20;

    protected const int WAIT_SECONDS = 3;

    /**
     * The services that supervisord starts in the image, by their names in supervisorctl, see docs/customization.md.
     *
     * @var list<string>
     */
    protected const array SERVICES = [];

    /**
     * Documented environment variables, each set to a value other than its default, whose effect the tests check in
     * the environment container of a class, see environmentContainer(). Variables whose effect would get in the way of
     * other tests, e.g. a start of Xdebug with every request, get a container of their own in their test.
     *
     * @var array<string, string>
     */
    protected const array ENVIRONMENT = [
        'TZ' => 'Europe/Berlin',
        'LOG_STDOUT' => '/tmp/image-test-stdout.log',
        'LOG_STDERR' => '/tmp/image-test-stderr.log',
        'SERVICE_SUPERVISOR_OPTS' => '--loglevel=trace',
        'SERVICE_SYSLOG_OPTS' => '--pidfile=/tmp/image-test-syslog-ng.pid',
        'SERVICE_SSH_OPTS' => '-p 2222',
        'SERVICE_APACHE_OPTS' => '-DIMAGE_TEST',
        'WEB_DOCUMENT_ROOT' => '/app/image-test-root',
        'WEB_DOCUMENT_INDEX' => 'start.html',
        'WEB_PHP_TIMEOUT' => '2',
        'SERVICE_NGINX_CLIENT_MAX_BODY_SIZE' => '1k',
        'WEB_NO_CACHE_PATTERN' => '\\.imagetest$',
        'PHP_DATE_TIMEZONE' => 'Europe/Berlin',
        'PHP_DISPLAY_ERRORS' => '1',
        'PHP_MEMORY_LIMIT' => '64M',
        'PHP_MAX_EXECUTION_TIME' => '1',
        'PHP_POST_MAX_SIZE' => '1M',
        'PHP_UPLOAD_MAX_FILESIZE' => '512K',
        'PHP_OPCACHE_MEMORY_CONSUMPTION' => '200',
        'PHP_OPCACHE_MAX_ACCELERATED_FILES' => '10000',
        'PHP_OPCACHE_VALIDATE_TIMESTAMPS' => '0',
        'PHP_OPCACHE_INTERNED_STRINGS_BUFFER' => '12',
        'PHP_SENDMAIL_PATH' => '/usr/sbin/sendmail -t -i -f image-test@example.test',
        'php.max_input_vars' => '10',
        'PHP_DISMOD' => 'redis,pgsql',
        'COMPOSER_VERSION' => '1',
        'SERVICE_PHPFPM_OPTS' => '-dsession.name=IMAGETEST',
        'FPM_PROCESS_MAX' => '2',
        'FPM_REQUEST_TERMINATE_TIMEOUT' => '4',
        'FPM_RLIMIT_FILES' => '4321',
        'FPM_RLIMIT_CORE' => '1024',
        'fpm.global.log_level' => 'debug',
        'fpm.pool.ping.path' => '/image-test-ping',
        'POSTFIX_RELAYHOST' => '[127.0.0.1]:2525',
        'POSTFIX_MYNETWORKS' => '127.0.0.2/32',
    ];

    private static ?ImageUnderTest $image = null;

    private const string FIXTURES = __DIR__ . '/Fixtures';

    private const string HOOKS = __DIR__ . '/Hooks';

    private static ?Container $container = null;

    /**
     * Further containers of the class, by name, see startContainer().
     *
     * @var array<string, Container>
     */
    private static array $containers = [];

    /**
     * The environment and command each further container was started with, by name.
     *
     * @var array<string, array{array<string, string>, list<string>, bool}>
     */
    private static array $containerArguments = [];

    /**
     * Why a further container did not start, by name: later tests fail with it at once instead of waiting again.
     *
     * @var array<string, RuntimeException>
     */
    private static array $containerFailures = [];

    /**
     * The names of the further containers of the current test, see startTestContainer().
     *
     * @var list<string>
     */
    private array $testContainers = [];

    /**
     * The hours of the cron job of the environment container, see cronHours().
     */
    private static ?string $cronHours = null;

    /**
     * Whether a check of this class already waited for all its attempts. The services then had their time to start,
     * so later checks try only once, and a broken image fails in minutes, not in hours.
     */
    private static bool $waitedForAllAttempts = false;

    public static function setUpBeforeClass(): void
    {
        self::$image = ImageUnderTest::fromEnvironment();
        self::$waitedForAllAttempts = false;
        self::$container = Container::start(self::$image->reference, [self::FIXTURES], self::labels());
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$containers as $container) {
            $container->remove();
        }

        self::$containers = [];
        self::$containerArguments = [];
        self::$containerFailures = [];
        self::$cronHours = null;
        self::$container?->remove();
        self::$container = null;
    }

    protected function tearDown(): void
    {
        foreach ($this->testContainers as $name) {
            (self::$containers[$name] ?? null)?->remove();
            unset(self::$containers[$name], self::$containerArguments[$name], self::$containerFailures[$name]);
        }

        $this->testContainers = [];
        parent::tearDown();
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

    /**
     * The image under test; also in data providers, which run before setUpBeforeClass().
     */
    protected static function image(): ImageUnderTest
    {
        return self::$image ??= ImageUnderTest::fromEnvironment();
    }

    protected static function container(): Container
    {
        return self::$container ?? throw new LogicException('The container is started in setUpBeforeClass().');
    }

    /**
     * The label of the run of ci/console test:image, which removes the containers of the run afterwards.
     *
     * @return array<string, string>
     */
    private static function labels(): array
    {
        $run = self::image()->run;
        return $run === '' ? [] : [ImageTests::CONTAINER_LABEL => $run];
    }

    /**
     * Another container of the image, started once per class and $name, with $environment and, instead of the image's
     * command, $command. It gets the files of Fixtures/ and Hooks/ too, unless $withFiles is false, and is removed after
     * the class.
     *
     * @param array<string, string> $environment
     * @param list<string> $command
     */
    protected function startContainer(string $name, array $environment = [], array $command = [], bool $withFiles = true): Container
    {
        if (isset(self::$containerArguments[$name]) && self::$containerArguments[$name] !== [$environment, $command, $withFiles]) {
            throw new LogicException(sprintf('The container "%s" was started with another environment or command; give this one another name.', $name));
        }

        self::$containerArguments[$name] = [$environment, $command, $withFiles];
        if (isset(self::$containerFailures[$name])) {
            throw self::$containerFailures[$name];
        }

        if (!isset(self::$containers[$name])) {
            try {
                self::$containers[$name] = Container::start(self::image()->reference, $withFiles ? [self::FIXTURES, self::HOOKS] : [], self::labels(), $environment, $command);
            } catch (RuntimeException $runtimeException) {
                self::$containerFailures[$name] = $runtimeException;
                throw $runtimeException;
            }

            // its services have not had their time yet
            self::$waitedForAllAttempts = false;
        }

        return self::$containers[$name];
    }

    /**
     * Like startContainer(), for the current test only: the container is removed after the test, so that it does not
     * run along with the tests after it.
     *
     * @param array<string, string> $environment
     * @param list<string> $command
     */
    protected function startTestContainer(string $name, array $environment = [], array $command = [], bool $withFiles = true): Container
    {
        $this->testContainers[] = $name;
        return $this->startContainer($name, $environment, $command, $withFiles);
    }

    /**
     * The services that supervisord starts in the image, see SERVICES.
     *
     * @return list<string>
     */
    protected static function services(): array
    {
        return static::SERVICES;
    }

    /**
     * The environment container of the class: started with the image's command and every variable of ENVIRONMENT,
     * SERVICE_CRON_OPTS for the cron of the distribution, and a cron job. php-test.php is also in its document root.
     */
    protected function environmentContainer(): Container
    {
        $container = $this->startContainer('environment', [
            ...static::ENVIRONMENT,
            'SERVICE_CRON_OPTS' => self::cronOptions(),
            // a cron job from Hooks/, for the time zone, see ContainerEnvironmentChecks
            'IMAGE_TEST_CRON' => $this->cronHours(),
        ]);
        // the PHP test page in the document root of WEB_DOCUMENT_ROOT, until it is there
        $documentRoot = escapeshellarg(static::ENVIRONMENT['WEB_DOCUMENT_ROOT']);
        $this->assertCommandSucceeds('test -f ' . $documentRoot . '/php-test.php || cp /app/php-test.php ' . $documentRoot . '/', $container);

        return $container;
    }

    /**
     * A log level option of cron in Debian and Ubuntu, of BusyBox crond in Alpine.
     */
    protected static function cronOptions(): string
    {
        return self::image()->isAlpine() ? '-l 8' : '-L 15';
    }

    /**
     * The current and the next hour in the time zone of TZ, e.g. "9,10", for the cron job of the environment container.
     * Worked out here and not in the image, whose time zone the test checks; once per class, so that the environment
     * of the container stays the same.
     */
    private function cronHours(): string
    {
        if (self::$cronHours === null) {
            $hour = (int) new DateTimeImmutable('now', new DateTimeZone(static::ENVIRONMENT['TZ']))->format('G');
            self::$cronHours = sprintf('%d,%d', $hour, ($hour + 1) % 24);
        }

        return self::$cronHours;
    }

    /**
     * The command lines of the processes in the container, with spaces between the arguments.
     *
     * @return list<string>
     */
    protected function commandLines(?Container $container = null): array
    {
        // a process may exit while the loop runs, so errors and the exit code do not count
        return $this->lines($this->execute('for file in /proc/[0-9]*/cmdline; do tr "\\000" " " 2>/dev/null < "$file"; echo; done', $container)->stdout);
    }

    /**
     * Runs $command in a new container of the image, through its entrypoint, and returns the result once the command
     * exits. The container gets the files of Fixtures/ too and is removed afterwards.
     *
     * @param list<string> $command
     * @param array<string, string> $environment
     */
    protected function runContainer(array $command, array $environment = []): CommandResult
    {
        return Container::run(self::image()->reference, [self::FIXTURES, self::HOOKS], self::labels(), $environment, $command);
    }

    /**
     * Runs $command with sh -c in the container of the class, or in $container.
     */
    protected function execute(string $command, ?Container $container = null): CommandResult
    {
        return ($container ?? self::container())->exec($command);
    }

    /**
     * Runs $command and asserts that it exits with 0.
     */
    protected function assertCommandSucceeds(string $command, ?Container $container = null): CommandResult
    {
        $result = $this->execute($command, $container);
        self::assertSame(0, $result->exitCode, $result->describe());
        return $result;
    }

    /**
     * Runs $command, asserts that it exits with 0 and returns its standard output.
     */
    protected function stdout(string $command, ?Container $container = null): string
    {
        return $this->assertCommandSucceeds($command, $container)->stdout;
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
     * Like eventually(), but always with all attempts: for the result of something the check itself just did, e.g. a
     * mail it sent, which still takes its time when the services of the class had theirs.
     */
    protected function waitFor(callable $check, int $attempts = self::ATTEMPTS, int $waitSeconds = self::WAIT_SECONDS): void
    {
        for ($attempt = 1;; ++$attempt) {
            try {
                $check();
                return;
            } catch (AssertionFailedError $failure) {
                if ($attempt >= $attempts) {
                    throw $failure;
                }

                sleep($waitSeconds);
            }
        }
    }

    /**
     * The shell command that sends a request straight to PHP-FPM on 127.0.0.1:9000, with
     * Fixtures/opt/image-tests/fastcgi-request.php, and prints the response, headers and body. $query is the query
     * string of $script, $parameters are further FastCGI parameters, e.g. REQUEST_METHOD. The command reads the request
     * body from its stdin.
     *
     * @param array<string, string> $parameters
     */
    protected static function phpFpmCommand(string $query, array $parameters = [], string $script = '/app/php-test.php'): string
    {
        $command = 'php -n /opt/image-tests/fastcgi-request.php 127.0.0.1:9000 ' . escapeshellarg($script) . ' ' . escapeshellarg($query);
        foreach ($parameters as $name => $value) {
            $command .= ' ' . escapeshellarg($name . '=' . $value);
        }

        return $command;
    }

    /**
     * Sends a request with phpFpmCommand() in $container and returns the result. $bodyCommand is a shell command whose
     * output is the request body. The request is not retried, see phpFpmBody().
     *
     * @param array<string, string> $parameters
     */
    protected function phpFpmRequest(Container $container, string $query, array $parameters = [], string $bodyCommand = '', string $script = '/app/php-test.php'): CommandResult
    {
        $command = self::phpFpmCommand($query, $parameters, $script);
        return $this->execute($bodyCommand === '' ? $command . ' < /dev/null' : $bodyCommand . ' | ' . $command, $container);
    }

    /**
     * The body of the response of PHP-FPM to phpFpmRequest(), retried while PHP-FPM starts.
     *
     * @param array<string, string> $parameters
     */
    protected function phpFpmBody(Container $container, string $query, array $parameters = [], string $bodyCommand = '', string $script = '/app/php-test.php'): string
    {
        $response = '';
        $this->eventually(function () use ($container, $query, $parameters, $bodyCommand, $script, &$response): void {
            $result = $this->phpFpmRequest($container, $query, $parameters, $bodyCommand, $script);
            self::assertSame(0, $result->exitCode, $result->describe());
            $response = $result->stdout;
        });

        return explode("\r\n\r\n", $response, 2)[1] ?? '';
    }

    /**
     * The number of worker processes of PHP-FPM in $container.
     */
    protected function phpFpmWorkers(Container $container): int
    {
        return count(preg_grep('/^php-fpm: pool /', $this->commandLines($container)) ?: []);
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
     * of the first successful response. $check asserts the body and is retried with the request. $container is the
     * container to request in, by default the one of the class.
     *
     * @param (callable(string): void)|null $check
     */
    protected function request(string $url, string $curlOptions = '', ?callable $check = null, ?Container $container = null): string
    {
        $body = '';
        $this->eventually(function () use ($url, $curlOptions, $check, $container, &$body): void {
            $result = $this->execute('curl --insecure --silent --show-error --fail ' . $curlOptions . ' ' . escapeshellarg($url), $container);
            self::assertSame(0, $result->exitCode, $result->describe());
            if ($check !== null) {
                $check($result->stdout);
            }

            $body = $result->stdout;
        });

        return $body;
    }

    /**
     * The HTTP status code of a request for $url with curl in the container of the class, or in $container, e.g. "404".
     * The request is not retried. $bodyCommand is a shell command whose output goes to the stdin of curl, e.g. for
     * --data-binary @- in $curlOptions.
     */
    protected function statusCode(string $url, string $curlOptions = '', ?Container $container = null, string $bodyCommand = ''): string
    {
        $command = "curl --silent --output /dev/null --write-out '%{http_code}' " . $curlOptions . ' ' . escapeshellarg($url);
        return $this->stdout($bodyCommand === '' ? $command : $bodyCommand . ' | ' . $command, $container);
    }

    /**
     * Asserts that PHP printed no notice, warning or fatal error into $output, spelled as PHP spells them.
     */
    protected function assertNoPhpErrors(string $output): void
    {
        foreach (['Notice', 'Warning', 'Fatal error'] as $message) {
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
