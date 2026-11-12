# webdevops/php and webdevops/php-dev

PHP-FPM on top of the official PHP image, with the webdevops entrypoint, `supervisord`, cron, syslog-ng and the `application` user. The `-dev` variant adds Xdebug, the Blackfire extension, Postfix and an SSH server for local development.

`webdevops/php-official` is built from the same Dockerfiles as `webdevops/php`.

## Tags

| Tags | Based on |
| --- | --- |
| `8.2`, `8.3`, `8.4`, `8.5` | `php:<version>-fpm-bookworm` (Debian 12) |
| `8.2-alpine`, `8.3-alpine`, `8.4-alpine`, `8.5-alpine` | `php:<version>-fpm-alpine` |

Both `webdevops/php` and `webdevops/php-dev` use these tags. For the differences between the Debian and the Alpine tags, see [Debian or Alpine](#debian-or-alpine).

Always use one of these tags: an image without a tag (`latest`) and the `alpine` tag are old builds from 2019 and 2020, see [Known issues](../known-issues.md#latest-and-alpine-tags-of-the-php-images).

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

Started by default: `php-fpm`, `cron`, `syslog`. The `-dev` variant also starts `postfix` and `ssh`. See [Customization](../customization.md#services). PHP-FPM listens on port `9000` of all IPv6 and IPv4 addresses; if the container has no IPv6, of all IPv4 addresses.

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

All of these can be changed with environment variables, see [PHP](../environment-variables.md#php). For larger uploads and long requests, see [Uploads and long requests](../environment-variables.md#uploads-and-long-requests); for production, see [Production](#production).

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

### Installing more extensions

`docker-php-ext-install` from the official PHP image still works for extensions that come with PHP's source code, for example `dba`. On Alpine it installs the build tools it needs and removes them again.

```dockerfile
FROM webdevops/php:8.5
RUN docker-php-ext-install dba
```

For extensions from PECL, use [PIE](https://github.com/php/pie). It does not have to stay in the image; mount it for the `RUN` that needs it. PIE enables the extension for PHP-FPM and the PHP CLI. The Debian images contain the build tools:

```dockerfile
FROM webdevops/php:8.5
RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    pie install pecl/pcov
```

On Alpine, install the build tools (`$PHPIZE_DEPS`) in the same `RUN` and remove them afterwards:

```dockerfile
FROM webdevops/php:8.5-alpine
RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pie install pecl/pcov \
    && apk del .build-deps
```

## Composer

Composer 2 is installed as `composer2`, Composer 1 as `composer1`. `composer` links to one of them: Composer 2 by default, Composer 1 with `COMPOSER_VERSION: "1"`.

`COMPOSER_VERSION` switches the link when a container starts, not during `docker build`. In a Dockerfile, `composer` is always Composer 2; call `composer1` or `composer2` directly:

```dockerfile
FROM webdevops/php:8.5
COPY . /app
RUN cd /app && composer2 install --no-interaction
```

## Debian or Alpine

Every PHP version comes as a Debian tag (`8.5`) and an Alpine tag (`8.5-alpine`). This applies to all `php-*` images. The differences:

| Topic | Debian | Alpine |
| --- | --- | --- |
| [Base image](#tags) | `php:<version>-fpm-bookworm` (Debian 12) | `php:<version>-fpm-alpine` |
| [Download size](https://hub.docker.com/r/webdevops/php/tags) of `webdevops/php:8.5` | about 430 MB | about 170 MB |
| [ionCube Loader](#php-extensions) | included | not included |
| [`imap` extension](#php-extensions) | `8.2` and `8.3` only | `8.2-alpine` and `8.3-alpine` only |
| [Build tools for extensions](#installing-more-extensions) | included | installed by `docker-php-ext-install`; for PIE install `$PHPIZE_DEPS` |
| [Locales](../customization.md#locales) | several hundred, for example `de_DE.utf8` | none |
| [Installing packages](../customization.md#tools-for-your-dockerfile) | `apt-install`, from the Debian components `main`, `contrib` and `non-free` | `apk-install` |
| [`docker-cronjob`](../customization.md#cron-jobs) | works | does not work, see [Known issues](../known-issues.md#docker-cronjob-on-alpine) |
| [`docker-image-cleanup`](../customization.md#tools-for-your-dockerfile) | works | fails, see [Known issues](../known-issues.md#docker-image-cleanup-in-the-alpine-php-images) |
| [Apache index files](../environment-variables.md#web-server) in `php-apache` | `index.html`, `index.cgi`, `index.pl`, `index.php`, `index.xhtml`, `index.htm` come before `WEB_DOCUMENT_INDEX` | `index.html` comes before `WEB_DOCUMENT_INDEX` |
| [`mail()` without Postfix](#mail) | fails: there is no `sendmail` command | fails: BusyBox `sendmail` finds no mail server |

## Production

Use the images without `-dev` in production. The `-dev` images load Xdebug and start Postfix and an SSH server, and `php-dev` sets the password `dev` for `root` and `application`.

Check these defaults for your application:

- **PHP-FPM processes:** `pm.max_children` is `5` (`FPM_PM_MAX_CHILDREN`). PHP-FPM handles at most five requests at the same time; further requests wait. When that happens, the container log shows `server reached pm.max_children setting (5), consider raising it`. Raise the value if the container has memory to spare; each process can use up to `memory_limit` (`512M`). If the log shows `seems busy (you may need to increase pm.start_servers, or pm.min/max_spare_servers)`, PHP-FPM starts processes too slowly for the load; raise `FPM_PM_START_SERVERS` and the spare server settings, see [PHP-FPM](../environment-variables.md#php-fpm).
- **Code in the image:** if your Dockerfile copies the application code into the image, set `PHP_OPCACHE_VALIDATE_TIMESTAMPS: "0"`. PHP then no longer checks the files for changes; a changed file takes effect only after PHP-FPM restarts. Do not use it with code mounted from the host.
- **Errors:** `display_errors` is `0` (`PHP_DISPLAY_ERRORS`), so error messages are not shown to visitors. Keep it that way.
- **Uploads and long requests:** the limits are in [Uploads and long requests](../environment-variables.md#uploads-and-long-requests).

```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    environment:
      FPM_PM_MAX_CHILDREN: "20"
      PHP_OPCACHE_VALIDATE_TIMESTAMPS: "0"
```

Check the values in the running container. `php-fpm -tt` prints the PHP-FPM configuration. `php-fpm -i` prints the PHP settings that PHP-FPM uses; `php -i` shows the values of the PHP CLI, which differ, for example `max_execution_time` is `0` there.

```sh
docker compose exec app php-fpm -tt
docker compose exec app php-fpm -i
```

## Mail

PHP's `mail()` passes each message to `/usr/sbin/sendmail`.

In the images without `-dev`, Postfix is not running and `mail()` returns `false`. The Debian images have no `/usr/sbin/sendmail`; in the Alpine images it is BusyBox's `sendmail`, which finds no mail server on `127.0.0.1`. To send mail with `mail()`, enable Postfix in your Dockerfile and set `POSTFIX_RELAYHOST` to a mail server that accepts mail from the container. There is no variable for a login at the relay host.

```dockerfile
FROM webdevops/php-nginx:8.5
RUN docker-service enable postfix
```

```yaml
services:
  app:
    build: .
    environment:
      POSTFIX_RELAYHOST: "[mail.example.com]:25"
```

The `-dev` images start Postfix. It has no relay host and delivers each message directly to the recipient's mail server.

> [!WARNING]
> Without a relay host, Postfix in a development container tries to deliver mail to real recipients. Send it to a mail catcher such as [Mailpit](https://mailpit.axllent.org/) instead.

```yaml
services:
  app:
    image: webdevops/php-nginx-dev:8.5
    ports:
      - "8080:80"
    volumes:
      - ./:/app
    environment:
      POSTFIX_RELAYHOST: "[mailpit]:1025"
  mailpit:
    image: axllent/mailpit
    ports:
      - "8025:8025"
```

Mail from PHP-FPM and from the PHP CLI then shows up in Mailpit at http://localhost:8025.

## Development variant (`php-dev`)

`php-dev` is built on `php` and changes the following:

- Xdebug is loaded by default (`xdebug.mode=debug`, `xdebug.start_with_request=trigger`, `xdebug.client_port=9003`), see [Xdebug with an IDE](#xdebug-with-an-ide). `PHP_DEBUGGER` switches to Blackfire or turns both off, see [Development images](../environment-variables.md#development-images). Known issue: the [Blackfire agent is missing](../known-issues.md#blackfire-agent).
- `opcache.revalidate_freq` is `0`, so changed files are picked up immediately.
- Postfix and an SSH server are started. Postfix sends mail to real recipients unless you set a relay host, see [Mail](#mail).
- `root` and `application` have the password `dev`. Use the `-dev` images for local development only.

### Xdebug with an IDE

Xdebug connects to your IDE on port `9003`. Set `php.xdebug.client_host` to the host the IDE runs on. `host.docker.internal` is the Docker host; Docker Desktop provides the name, on Linux add it with `extra_hosts`:

```yaml
services:
  app:
    image: webdevops/php-nginx-dev:8.5
    ports:
      - "8080:80"
    volumes:
      - ./:/app
    environment:
      php.xdebug.client_host: host.docker.internal
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

`XDEBUG_CLIENT_HOST: host.docker.internal` has the same effect.

- **Start a debug session:** Xdebug only connects when the request carries the trigger `XDEBUG_SESSION`. In the browser, add `?XDEBUG_SESSION=1` to the URL, or set the cookie `XDEBUG_SESSION` as browser extensions for Xdebug do. On the command line, set the environment variable:

  ```sh
  docker compose exec -u application -e XDEBUG_SESSION=1 app php bin/console app:import
  ```

- **Path mapping:** Xdebug reports the paths inside the container, for example `/app/public/index.php`. In the IDE, map your project directory to `/app`.
- **Client discovery:** `xdebug.discover_client_host` is `1`. For browser requests Xdebug first connects to the address in the `X-Forwarded-For` header or, without it, to the address the request came from. Only if that fails does it use `xdebug.client_host`. On the command line there is no such address, and Xdebug always uses `xdebug.client_host`. That is why the example sets it.
