<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageUnderTest;

use function array_diff;
use function array_values;

/**
 * The PHP extensions every PHP image loads, by their names in "php -m" and get_loaded_extensions().
 */
final class PhpModules
{
    private const array COMMON = [
        'amqp', 'bcmath', 'bz2', 'calendar', 'Core', 'ctype', 'curl', 'date', 'dom', 'exif', 'excimer', 'FFI',
        'fileinfo', 'filter', 'ftp', 'gettext', 'gmp', 'hash', 'iconv', 'imagick', 'intl', 'json', 'ldap', 'libxml',
        'mbstring', 'memcached', 'mongodb', 'mysqli', 'mysqlnd', 'openssl', 'opentelemetry', 'pcntl', 'pcre', 'PDO',
        'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'pgsql', 'Phar', 'posix', 'protobuf', 'Reflection', 'session',
        'SimpleXML', 'soap', 'sockets', 'SPL', 'sqlite3', 'standard', 'sysvmsg', 'sysvsem', 'sysvshm', 'tokenizer',
        'vips', 'xml', 'xmlreader', 'xmlrpc', 'xmlwriter', 'xsl', 'yaml', 'zip', 'zlib', 'gd', 'apcu',
    ];

    /**
     * Extensions that "php -m" lists, with imap where the PHP version has it.
     *
     * @return list<string>
     */
    public static function cli(ImageUnderTest $image): array
    {
        return $image->hasImap() ? [...self::COMMON, 'imap'] : self::COMMON;
    }

    /**
     * Extensions that PHP-FPM loads: the same, except pcntl, which PHP-FPM disables.
     *
     * @return list<string>
     */
    public static function fpm(ImageUnderTest $image): array
    {
        return array_values(array_diff(self::cli($image), ['pcntl']));
    }
}
