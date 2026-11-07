# webdevops/php and webdevops/php-dev

PHP-FPM on top of the official PHP image, with the webdevops entrypoint, `supervisord`, cron, syslog-ng and the `application` user. The `-dev` variant adds Xdebug, the Blackfire extension, Postfix and an SSH server for local development.

`webdevops/php-official` is built from the same Dockerfiles as `webdevops/php`.

## Tags

| Tags | Based on |
| --- | --- |
| `8.2`, `8.3`, `8.4`, `8.5` | `php:<version>-fpm-bookworm` (Debian 12) |
| `8.2-alpine`, `8.3-alpine`, `8.4-alpine`, `8.5-alpine` | `php:<version>-fpm-alpine` |

Both `webdevops/php` and `webdevops/php-dev` use these tags. Always use one of them: an image without a tag (`latest`) and the `alpine` tag are old builds from 2019 and 2020, see [Known issues](../known-issues.md#latest-and-alpine-tags-of-the-php-images).

## Usage

```yaml
services:
  php:
    image: webdevops/php:8.5
    volumes:
      - ./:/app
```

PHP-FPM listens on port `9000`. For a web server in the same container use [`php-nginx`](php-nginx.md) or [`php-apache`](php-apache.md).

## Services

Started by default: `php-fpm`, `cron`, `syslog`. The `-dev` variant also starts `postfix` and `ssh`. See [Customization](../customization.md#services). PHP-FPM listens on `[::]:9000`; without IPv6 in the container it does not start, see [Known issues](../known-issues.md#nginx-and-php-fpm-without-ipv6).

## PHP configuration

| Setting | Value |
| --- | --- |
| `memory_limit` | `512M` |
| `max_execution_time` | `300` |
| `post_max_size`, `upload_max_filesize` | `50M` |
| `max_input_vars` | `5000` |
| `date.timezone` | `UTC` |
| `display_errors` | `0` |
| `session.upload_progress.enabled` | `0` |
| OPcache | enabled, `opcache.memory_consumption` `256`, `opcache.revalidate_freq` `2` |

All of these can be changed with environment variables, see [PHP](../environment-variables.md#php).

### Session upload progress

All PHP images disable `session.upload_progress.enabled` by default. Session upload progress lets PHP write request-controlled data into the session while a multipart upload is parsed, before application code runs. Regular file uploads (`$_FILES`) and sessions are not affected. To turn it back on, set `php.session.upload_progress.enabled: "1"`.

Applications that read the upload progress from the session, using a form field named like `session.upload_progress.name` (default `PHP_SESSION_UPLOAD_PROGRESS`), need this setting turned on. That includes cancelling an upload through the session (`cancel_upload`). Calling `ini_set()` in the application does not help: it returns `false` and the value stays off. Progress bars that measure the upload in the browser are not affected.

## PHP extensions

All tags contain these extensions:

`amqp`, `apcu`, `bcmath`, `bz2`, `calendar`, `ctype`, `curl`, `date`, `dom`, `excimer`, `exif`, `ffi`, `fileinfo`, `filter`, `ftp`, `gd`, `gettext`, `gmp`, `hash`, `iconv`, `imagick`, `intl`, `json`, `ldap`, `libxml`, `mbstring`, `memcached`, `mongodb`, `mysqli`, `mysqlnd`, `openssl`, `opentelemetry`, `pcntl`, `pcre`, `pdo`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `pgsql`, `phar`, `posix`, `protobuf`, `random`, `readline`, `redis`, `reflection`, `session`, `shmop`, `simplexml`, `soap`, `sockets`, `sodium`, `spl`, `sqlite3`, `standard`, `sysvmsg`, `sysvsem`, `sysvshm`, `tokenizer`, `vips`, `xml`, `xmlreader`, `xmlrpc`, `xmlwriter`, `xsl`, `yaml`, `Zend OPcache`, `zip`, `zlib`

Differences between tags:

| Extension | Tags |
| --- | --- |
| `imap` | `8.2`, `8.3`, `8.2-alpine`, `8.3-alpine` |
| ionCube Loader | Debian-based tags (`8.2` to `8.5`) |
| `lexbor`, `uri` | `8.5`, `8.5-alpine` |

Disable extensions you do not need with `PHP_DISMOD`, for example `PHP_DISMOD: redis,apcu`.

## Composer

Composer 2 is installed as `composer2`, Composer 1 as `composer1`. `composer` links to one of them: Composer 2 by default, Composer 1 with `COMPOSER_VERSION: "1"`.

`COMPOSER_VERSION` switches the link when a container starts, not during `docker build`. In a Dockerfile, `composer` is always Composer 2; call `composer1` or `composer2` directly:

```dockerfile
FROM webdevops/php:8.5
COPY . /app
RUN cd /app && composer2 install --no-interaction
```

## Development variant (`php-dev`)

`php-dev` is built on `php` and changes the following:

- Xdebug is loaded by default (`xdebug.mode=debug`, `xdebug.start_with_request=trigger`, `xdebug.client_port=9003`). `PHP_DEBUGGER` switches to Blackfire or turns both off, see [Development images](../environment-variables.md#development-images). Known issues: the [`XDEBUG_*` variables are written into your own `php.ini`](../known-issues.md#xdebug-settings-written-into-phpini), and the [Blackfire agent is missing](../known-issues.md#blackfire-agent).
- `opcache.revalidate_freq` is `0`, so changed files are picked up immediately.
- Postfix and an SSH server are started.
- `root` and `application` have the password `dev`. Use the `-dev` images for local development only.
