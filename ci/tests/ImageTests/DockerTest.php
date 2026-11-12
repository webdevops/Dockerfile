<?php

declare(strict_types=1);

namespace Webdevops\Build\Tests\ImageTests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webdevops\Build\ImageTests\Docker\CommandResult;
use Webdevops\Build\ImageTests\Docker\FileArchive;
use Webdevops\Build\ImageTests\Docker\FileInfo;
use Webdevops\Build\ImageTests\Docker\ListeningSockets;
use Webdevops\Build\Tests\TemporaryRepository;

use function ceil;
use function chmod;
use function file_get_contents;
use function ksort;
use function mkdir;
use function octdec;
use function rtrim;
use function strlen;
use function substr;
use function trim;

/**
 * The parts of the image test harness that work without a container.
 */
#[CoversClass(FileInfo::class)]
#[CoversClass(ListeningSockets::class)]
#[CoversClass(CommandResult::class)]
#[CoversClass(FileArchive::class)]
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

    public function testFileArchiveHoldsTheFilesReadableByEverybodyWithoutDirectories(): void
    {
        $repository = new TemporaryRepository();
        try {
            $repository->write('files/app/index.php', "<?php echo 'ok';\n");
            $repository->write('files/entrypoint.d/50-marker.sh', "touch /tmp/marker\n");
            // a checkout with umask 077
            chmod($repository->path . '/files/app/index.php', 0600);
            chmod($repository->path . '/files/entrypoint.d/50-marker.sh', 0700);

            $archive = FileArchive::create($repository->path . '/files');
            try {
                self::assertSame([
                    'app/index.php' => ['0', 0644],
                    'entrypoint.d/50-marker.sh' => ['0', 0755],
                ], $this->tarEntries($archive->path), 'only files, readable by everybody; no entries for app/ and entrypoint.d/');
            } finally {
                $archive->remove();
            }

            self::assertFalse($archive->exists());
        } finally {
            $repository->remove();
        }
    }

    public function testFileArchiveOfADirectoryWithoutFiles(): void
    {
        $repository = new TemporaryRepository();
        try {
            mkdir($repository->path . '/files/empty', 0755, true);

            $archive = FileArchive::create($repository->path . '/files');

            self::assertFalse($archive->exists(), 'nothing to copy');
            $archive->remove();
        } finally {
            $repository->remove();
        }
    }

    /**
     * The entries of a tar archive: path => [type flag, mode]. Type flag 0 is a file, 5 a directory.
     *
     * @return array<string, array{string, int}>
     */
    private function tarEntries(string $path): array
    {
        $tar = (string)file_get_contents($path);
        $entries = [];
        $offset = 0;
        while ($offset + 512 <= strlen($tar) && trim(substr($tar, $offset, 512), "\0") !== '') {
            $header = substr($tar, $offset, 512);
            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            $entries[$prefix === '' ? $name : $prefix . '/' . $name] = [$header[156], (int)octdec(trim(substr($header, 100, 8), "\0 ")) & 0777];
            $offset += 512 + (int)ceil(octdec(trim(substr($header, 124, 12), "\0 ")) / 512) * 512;
        }

        ksort($entries);
        return $entries;
    }
}
