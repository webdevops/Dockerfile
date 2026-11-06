<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageUnderTest;

/**
 * The PHP extensions every PHP image loads, in the CLI and in PHP-FPM, by their names in "php -m" and
 * get_loaded_extensions().
 */
final class PhpModules
{
    private const array COMMON = [
        'amqp', 'bcmath', 'bz2', 'calendar', 'Core', 'ctype', 'curl', 'date', 'dom', 'exif', 'excimer', 'FFI',
        'fileinfo', 'filter', 'ftp', 'gettext', 'gmp', 'hash', 'iconv', 'imagick', 'intl', 'json', 'ldap', 'libxml',
        'mbstring', 'memcached', 'mongodb', 'mysqli', 'mysqlnd', 'openssl', 'opentelemetry', 'pcntl', 'pcre', 'PDO',
        'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'pgsql', 'Phar', 'posix', 'protobuf', 'Reflection', 'session',
        'SimpleXML', 'soap', 'sockets', 'SPL', 'sqlite3', 'standard', 'sysvmsg', 'sysvsem', 'sysvshm', 'tokenizer',
        'xml', 'xmlreader', 'xmlwriter', 'xsl', 'yaml', 'zip', 'zlib', 'gd', 'apcu', 'shmop', 'Zend OPcache', 'redis',
    ];

    /**
     * The extensions, with imap, vips and xmlrpc where the PHP version has them. PHP-FPM loads the same ones as the
     * CLI, pcntl included.
     *
     * @return list<string>
     */
    public static function loaded(ImageUnderTest $image): array
    {
        $modules = self::COMMON;
        if ($image->hasImap()) {
            $modules[] = 'imap';
        }

        if ($image->hasVipsAndXmlrpc()) {
            $modules[] = 'vips';
            $modules[] = 'xmlrpc';
        }

        return $modules;
    }
}
