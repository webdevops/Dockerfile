# Environment variables

The container entrypoint applies these variables every time a container starts. That includes the default `supervisord` command and one-off commands such as `docker compose run --rm app php -v`. Recreate the container after changing a value.

Each section names the images it applies to. Image names are given without tag; `php-*` means `php`, `php-dev`, `php-nginx`, `php-nginx-dev`, `php-apache` and `php-apache-dev`.

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    environment:
      WEB_DOCUMENT_ROOT: /app/public
      PHP_MEMORY_LIMIT: 1G
      php.max_input_vars: "10000"
```

## Application user

Images: `base-app`, `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `APPLICATION_USER` | `application` | User the `cli` entrypoint runs `CLI_SCRIPT` as. |
| `APPLICATION_GROUP` | `application` | Information only. The group is created when the image is built; changing the variable has no effect. |
| `APPLICATION_UID` | `1000` | Information only: UID of the `application` user. |
| `APPLICATION_GID` | `1000` | Information only: GID of the `application` group. |
| `APPLICATION_PATH` | `/app` | Information only: `/app` exists and belongs to `application`. |

## Container

Images: `base`, `base-app`, `apache`, `nginx`, `vsftp`, `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `CLI_SCRIPT` | not set | Command the `cli` entrypoint runs as `APPLICATION_USER`. Works in `base-app` and `php-*` only; in the other images `cli` fails because `APPLICATION_USER` is not set. See [Customization](customization.md#commands). |
| `LOG_STDOUT` | container stdout | File that `/docker.stdout` points to. Nginx, Apache, PHP-FPM and syslog-ng write their logs to `/docker.stdout` and `/docker.stderr`, vsftpd to `/docker.stdout`. |
| `LOG_STDERR` | container stderr | File that `/docker.stderr` points to. |

## Web server

Images: `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`, `nginx`, `apache`.

| Variable | Default | Effect |
| --- | --- | --- |
| `WEB_DOCUMENT_ROOT` | `/app` | Document root of the default virtual host. |
| `WEB_DOCUMENT_INDEX` | `index.php` | Directory index. Nginx uses only this file. Apache adds it after the distribution's own index files (Debian: `index.html`, `index.cgi`, `index.pl`, `index.php`, `index.xhtml`, `index.htm`; Alpine: `index.html`); if one of those exists, Apache serves it instead. |
| `WEB_ALIAS_DOMAIN` | `*.vm` | Additional host names of the default virtual host (`server_name` in Nginx, `ServerAlias` in Apache). |
| `WEB_PHP_SOCKET` | `127.0.0.1:9000` (empty in `nginx` and `apache`) | Address of PHP-FPM that PHP requests are passed to. |
| `WEB_PHP_TIMEOUT` | `600` | Timeout in seconds for PHP requests (`fastcgi_read_timeout` in Nginx, proxy timeout in Apache). |
| `SERVICE_NGINX_CLIENT_MAX_BODY_SIZE` | `50m` | Nginx `client_max_body_size`. Nginx-based images only. |
| `WEB_NO_CACHE_PATTERN` | `\.(css\|js\|gif\|png\|jpg\|svg\|json\|xml)$` | Development images only. Files matching this regular expression are served without caching (Nginx: `expires off`, Apache: no `ETag`). |

## PHP

Images: `php-*`.

`php.<key>` sets any php.ini directive. The part after `php.` is the directive name, for example `php.max_input_vars: "10000"` or `php.session.upload_progress.enabled: "1"`.

The following shortcuts set a single directive:

| Variable | php.ini directive | Default |
| --- | --- | --- |
| `PHP_DATE_TIMEZONE` | `date.timezone` | `UTC` |
| `PHP_DISPLAY_ERRORS` | `display_errors` | `0` |
| `PHP_MEMORY_LIMIT` | `memory_limit` | `512M` |
| `PHP_MAX_EXECUTION_TIME` | `max_execution_time` | `300` (PHP CLI always uses `0`) |
| `PHP_POST_MAX_SIZE` | `post_max_size` | `50M` |
| `PHP_UPLOAD_MAX_FILESIZE` | `upload_max_filesize` | `50M` |
| `PHP_OPCACHE_MEMORY_CONSUMPTION` | `opcache.memory_consumption` | `256` |
| `PHP_OPCACHE_MAX_ACCELERATED_FILES` | `opcache.max_accelerated_files` | `7963` |
| `PHP_OPCACHE_VALIDATE_TIMESTAMPS` | `opcache.validate_timestamps` | `1` |
| `PHP_OPCACHE_REVALIDATE_FREQ` | `opcache.revalidate_freq` | `2` (`0` in development images) |
| `PHP_OPCACHE_INTERNED_STRINGS_BUFFER` | `opcache.interned_strings_buffer` | `16` |
| `PHP_SENDMAIL_PATH` | `sendmail_path` | `/usr/sbin/sendmail -t -i` |

Two more variables control PHP itself:

| Variable | Default | Effect |
| --- | --- | --- |
| `PHP_DISMOD` | not set | Comma-separated list of extensions to disable, for example `redis,apcu`. Each entry matches part of an extension's ini file name, so `pgsql` also disables `pdo_pgsql`. Extensions compiled into PHP, such as `json`, cannot be disabled. |
| `COMPOSER_VERSION` | `2` | Major version the `composer` command runs: `2` or `1`. Composer 1 prints deprecation notices on PHP 8.4 and newer. |

## PHP-FPM

Images: `php-*`.

`fpm.global.<key>` sets a directive in the `[global]` section of the PHP-FPM configuration, for example `fpm.global.log_level: debug`. `fpm.pool.<key>` sets a directive in the pool (section `[www]` in `/opt/docker/etc/php/fpm/pool.d/application.conf`), for example `fpm.pool.pm.max_requests: "500"`.

The following shortcuts set a single directive:

| Variable | PHP-FPM directive | Default |
| --- | --- | --- |
| `FPM_PROCESS_MAX` | `process.max` | `0` |
| `FPM_PM_MAX_CHILDREN` | `pm.max_children` | `5` |
| `FPM_PM_START_SERVERS` | `pm.start_servers` | `2` |
| `FPM_PM_MIN_SPARE_SERVERS` | `pm.min_spare_servers` | `1` |
| `FPM_PM_MAX_SPARE_SERVERS` | `pm.max_spare_servers` | `3` |
| `FPM_PROCESS_IDLE_TIMEOUT` | `pm.process_idle_timeout` | `10s` |
| `FPM_MAX_REQUESTS` | `pm.max_requests` | `0` |
| `FPM_REQUEST_TERMINATE_TIMEOUT` | `request_terminate_timeout` | `0` |
| `FPM_RLIMIT_FILES` | `rlimit_files` | `0` |
| `FPM_RLIMIT_CORE` | `rlimit_core` | `0` |

The pool uses `pm = dynamic` and runs as `application`.

## Development images

Images: `php-dev`, `php-nginx-dev`, `php-apache-dev`.

| Variable | Default | Effect |
| --- | --- | --- |
| `PHP_DEBUGGER` | not set (Xdebug) | Debugger extension to load: `xdebug`, `blackfire` or `none`. The image contains the Blackfire PHP extension, but not the Blackfire agent. With `blackfire`, supervisord still starts the `blackfire-agent` service; it exits because the agent is missing and is restarted over and over, which fills the container log. |
| `XDEBUG_MODE` | `debug` | `xdebug.mode` |
| `XDEBUG_START_WITH_REQUEST` | `trigger` | `xdebug.start_with_request` |
| `XDEBUG_CLIENT_HOST` | `localhost` | `xdebug.client_host` |
| `XDEBUG_CLIENT_PORT` | `9003` | `xdebug.client_port` |
| `XDEBUG_DISCOVER_CLIENT_HOST` | `1` | `xdebug.discover_client_host` |
| `XDEBUG_TRIGGER_VALUE` | empty | `xdebug.trigger_value` |
| `XDEBUG_OUTPUT_DIR` | `/tmp/debug` | `xdebug.output_dir` |
| `BLACKFIRE_SERVER_ID` | not set | `blackfire.server_id`, used with `PHP_DEBUGGER=blackfire` |
| `BLACKFIRE_SERVER_TOKEN` | not set | `blackfire.server_token`, used with `PHP_DEBUGGER=blackfire` |

## Services

Each service reads its options when it starts. A service only runs if it is enabled; see [Customization](customization.md#services).

| Variable | Effect |
| --- | --- |
| `SERVICE_SUPERVISOR_OPTS` | Extra arguments for `supervisord`, for example `--loglevel=debug`. |
| `SERVICE_SYSLOG_OPTS` | Extra arguments for `syslog-ng`. |
| `SERVICE_CRON_OPTS` | Extra arguments for `cron -f` (Debian/Ubuntu) or `crond -f` (Alpine). |
| `SERVICE_SSH_OPTS` | Extra arguments for `sshd -D`. |
| `SERVICE_DNSMASQ_OPTS` | Extra arguments for `dnsmasq`. |
| `SERVICE_DNSMASQ_USER` | User `dnsmasq` runs as. Default: `root`. |
| `SERVICE_NGINX_OPTS` | Extra arguments for `nginx`, appended after `-g 'daemon off;'`. |
| `SERVICE_APACHE_OPTS` | Extra arguments for Apache, for example `-DMYFLAG`. |
| `SERVICE_PHPFPM_OPTS` | Extra arguments for `php-fpm`, for example `-dmax_input_time=120`. |
| `SERVICE_VSFTP_OPTS` | Extra arguments for `vsftpd`. |
| `POSTFIX_RELAYHOST` | Postfix `relayhost`, for example `[mail.example.com]:587`. |
| `POSTFIX_MYNETWORKS` | Postfix `mynetworks`. |
| `VIRTUAL_HOST` | Comma-separated host names. When `dnsmasq` starts, each one is added to its configuration as `address=/<host>/127.0.0.1`; `*.example.com` becomes `address=/.example.com/127.0.0.1`. Only the first wildcard entry is kept; further `*.` entries are dropped. |

## FTP

Image: `vsftp`.

| Variable | Default | Effect |
| --- | --- | --- |
| `FTP_USER` | `application` | FTP user name |
| `FTP_PASSWORD` | `application` | FTP password |
| `FTP_UID` | `1000` | UID of the FTP user |
| `FTP_GID` | `1000` | GID of the FTP user |

The FTP user's home directory is `/home/<FTP_USER>`; uploads are stored there.
