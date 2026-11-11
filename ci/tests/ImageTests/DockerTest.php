<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ImageTests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\ImageTests\Docker\CommandResult;
use Webdevops\Build\ImageTests\Docker\FileInfo;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;

/**
 * The parts of the image test harness that work without a container.
 */
#[CoversClass(FileInfo::class)]
#[CoversClass(ListeningSockets::class)]
#[CoversClass(CommandResult::class)]
final class DockerTest extends TestCase
{
    public function testFileInfoOfAnExecutableFile(): void
    {
        $file = FileInfo::parse('/usr/local/bin/service', "root|root|755\nefx\n");

        self::assertTrue($file->exists);
        self::assertTrue($file->isFile);
        self::assertFalse($file->isDirectory);
        self::assertFalse($file->isSymlink);
        self::assertTrue($file->isExecutable);
        self::assertSame('root', $file->owner);
        self::assertSame(0755, $file->mode);
        self::assertTrue($file->permits('owner', 'w'));
        self::assertFalse($file->permits('group', 'w'));
        self::assertTrue($file->permits('others', 'x'));
    }

    public function testFileInfoOfAPrivateDirectory(): void
    {
        $file = FileInfo::parse('/opt/docker/etc/nginx/ssl', "root|root|750\nedx\n");

        self::assertTrue($file->isDirectory);
        self::assertTrue($file->permits('group', 'r'));
        self::assertFalse($file->permits('others', 'r'));
        self::assertFalse($file->permits('others', 'x'));
    }

    public function testFileInfoOfAMissingPath(): void
    {
        $file = FileInfo::parse('/etc/cron.daily/dpkg', "\n\n");

        self::assertFalse($file->exists);
        self::assertFalse($file->isFile);
        self::assertSame('', $file->owner);
        self::assertSame(0, $file->mode);
    }

    public function testFileInfoOfASymbolicLink(): void
    {
        // test -d follows the link, stat does not
        $file = FileInfo::parse('/usr/local/etc/php-fpm.d', "root|root|777\nedLx\n");

        self::assertTrue($file->isSymlink);
        self::assertTrue($file->isDirectory);
        self::assertSame(0777, $file->mode);
    }

    public function testListeningSocketsFromTheKernelTables(): void
    {
        $sockets = ListeningSockets::parse(<<<'TABLES'
              sl  local_address rem_address   st tx_queue rx_queue tr tm->when retrnsmt   uid  timeout inode
               0: 00000000:0050 00000000:0000 0A 00000000:00000000 00:00000000 00000000     0        0 1001 1 0 100 0 0 10 0
               1: 0100007F:2328 00000000:0000 0A 00000000:00000000 00:00000000 00000000     0        0 1002 1 0 100 0 0 10 0
               2: 0100007F:0050 0100007F:A2C4 01 00000000:00000000 00:00000000 00000000     0        0 1003 1 0 20 4 30 10 -1
              sl  local_address                         remote_address                        st tx_queue rx_queue tr tm->when retrnsmt   uid  timeout inode
               0: 00000000000000000000000000000000:01BB 00000000000000000000000000000000:0000 0A 00000000:00000000 00:00000000 00000000     0        0 1004 1 0 100 0 0 10 0
               1: 00000000000000000000000001000000:2328 00000000000000000000000000000000:0000 0A 00000000:00000000 00:00000000 00000000     0        0 1005 1 0 100 0 0 10 0
            TABLES);

        self::assertSame('0.0.0.0:80, 127.0.0.1:9000, [::]:443, [::1]:9000', $sockets->describe());
        self::assertTrue($sockets->isListening(80));
        self::assertTrue($sockets->isListening(80, '0.0.0.0'));
        self::assertFalse($sockets->isListening(80, '::'));
        self::assertTrue($sockets->isListening(443, '::'));
        self::assertTrue($sockets->isListening(9000, '127.0.0.1'));
        self::assertTrue($sockets->isListening(9000, '::1'));
        self::assertFalse($sockets->isListening(9000, '0.0.0.0'));
        self::assertFalse($sockets->isListening(22), 'an established connection is not listening');
        self::assertSame('none', ListeningSockets::parse('')->describe());
    }

    public function testCommandResultDescribesItself(): void
    {
        self::assertSame(
            "php -m\nexit code: 1\nstdout: Core\nstderr: PHP Warning",
            new CommandResult('php -m', "Core\n", "PHP Warning\n", 1)->describe(),
        );
    }
}
