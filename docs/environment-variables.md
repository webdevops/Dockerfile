# Environment variables

The container entrypoint applies these variables every time a container starts. That includes the default `supervisord` command and one-off commands such as `docker compose run --rm app php -v`. Recreate the container after changing a value.

Each section names the images it applies to. Image names are given without tag; `php-*` means `php`, `php-dev`, `php-nginx`, `php-nginx-dev`, `php-apache` and `php-apache-dev`.

Some variables are set in the images but have no effect. They are listed in [Known issues](known-issues.md), not here.

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

Images: `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `APPLICATION_USER` | `application` | User the `cli` entrypoint runs `CLI_SCRIPT` as; if it is empty, `application`. |
| `APPLICATION_GROUP` | `application` | Information only. The group is created when the image is built; changing the variable has no effect. |
| `APPLICATION_UID` | `1000` | Information only: UID of the `application` user. To change it, see [Other UID or GID](customization.md#other-uid-or-gid). |
| `APPLICATION_GID` | `1000` | Information only: GID of the `application` group. To change it, see [Other UID or GID](customization.md#other-uid-or-gid). |
| `APPLICATION_PATH` | `/app` | Information only: `/app` exists and belongs to `application`. |

## Container

Images: `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `CLI_SCRIPT` | not set | Command the `cli` entrypoint runs as `APPLICATION_USER`. See [Customization](customization.md#commands). |
| `LOG_STDOUT` | container stdout | Not recommended: file that `/docker.stdout` points to, instead of the stdout of the container. All services write there what goes to stdout: Nginx, Apache and PHP-FPM their access logs, syslog-ng the messages of the services up to the level `warning`, for example of cron and Postfix, and supervisord what the services print to their stdout, for example in Alpine the output of cron jobs. `docker compose logs` no longer shows it, and the image does not rotate the file. The messages of supervisord itself, for example `spawned: 'nginxd'`, stay in the container log. A file that does not exist yet is created as `root:root` with mode `0644` at the start of the container; a file that exists keeps its owner and mode. If it cannot be created, for example in a read-only directory, the container starts all the same, and the log says so. See [Logs](customization.md#logs). |
| `LOG_STDERR` | container stderr | Not recommended, as `LOG_STDOUT`: file that `/docker.stderr` points to. Nginx and Apache write their error logs there, PHP-FPM its own messages and the errors of the PHP scripts it runs, syslog-ng the messages of the services from the level `err`, and supervisord what the services print to their stderr, for example the messages of sshd and of the Blackfire agent. `php.error_log` gives the PHP errors of PHP-FPM and the PHP CLI a file of their own instead, in a directory that `application` may write to, for example `/app`. A new file is created as for `LOG_STDOUT`. |
| `TZ` | not set (UTC) | Time zone of the container, for example `Europe/Berlin`. It applies to `date`, cron and the time stamps supervisord writes to the container log. PHP uses `PHP_DATE_TIMEZONE` instead, see [Time zone](customization.md#time-zone). |

## Web server

Images: `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`.

| Variable | Default | Effect |
| --- | --- | --- |
| `WEB_DOCUMENT_ROOT` | `/app` | Document root of the default virtual host. |
| `WEB_DOCUMENT_INDEX` | `index.php` | Directory index. Nginx uses only this file. Apache adds it after the distribution's own index files (Debian: `index.html`, `index.cgi`, `index.pl`, `index.php`, `index.xhtml`, `index.htm`; Alpine: `index.html`); if one of those exists, Apache serves it instead. |
| `WEB_ALIAS_DOMAIN` | `*.vm` | Additional host names of the default virtual host (`server_name` in Nginx, `ServerAlias` in Apache). |
| `WEB_PHP_SOCKET` | `127.0.0.1:9000` | Address of PHP-FPM that PHP requests are passed to. |
| `WEB_PHP_TIMEOUT` | `600` | Timeout in seconds for PHP requests (`fastcgi_read_timeout` in Nginx, proxy timeout in Apache). See [Long requests](#long-requests). |
| `SERVICE_NGINX_CLIENT_MAX_BODY_SIZE` | `50m` | Nginx `client_max_body_size`. Nginx-based images only. See [Uploads](#uploads). |
| `WEB_NO_CACHE_PATTERN` | `\.(css\|js\|gif\|png\|jpg\|svg\|json\|xml)$` | Development images only. Files matching this regular expression are served without caching (Nginx: `expires off`, Apache: no `ETag`). |

## PHP

Images: `php-*`.

`php.<key>` sets any php.ini directive. The part after `php.` is the directive name, for example `php.max_input_vars: "10000"` or `php.session.upload_progress.enabled: "1"`.

The values are written to `98-webdevops.ini`. Your own `php.ini` is loaded after it and wins, see [Configuration files](customization.md#php).

The following shortcuts set a single directive:

| Variable | php.ini directive | Default |
| --- | --- | --- |
| `PHP_DATE_TIMEZONE` | `date.timezone` | `UTC`. Cron and the system use `TZ`, see [Time zone](customization.md#time-zone). |
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
| `PHP_SENDMAIL_PATH` | `sendmail_path`; also set in the PHP-FPM pool, where it wins over `php.ini` | `/usr/sbin/sendmail -t -i` |

Two more variables control PHP itself:

| Variable | Default | Effect |
| --- | --- | --- |
| `PHP_DISMOD` | not set | Comma-separated list of extensions to disable, for example `redis,apcu`. Each entry matches part of an extension's ini file name, so `pgsql` also disables `pdo_pgsql`. Extensions compiled into PHP, such as `json`, cannot be disabled. |
| `COMPOSER_VERSION` | `2` | Major version the `composer` command runs: `2` or `1`. Applied when a container starts; during `docker build`, `composer` is Composer 2, see [Composer](images/php.md#composer). Composer 1 prints deprecation notices on PHP 8.4 and newer and cannot download packages from PHP 8.5 on, see [Known issues](known-issues.md#composer-1-on-php-84-to-86). |

## PHP-FPM

Images: `php-*`.

`fpm.global.<key>` sets a directive in the `[global]` section of the PHP-FPM configuration, for example `fpm.global.log_level: debug`. `fpm.pool.<key>` sets a directive in the pool `[www]`, for example `fpm.pool.pm.max_requests: "500"`, also one that the official PHP image sets in its pool file `docker.conf`, such as `clear_env` or `access.log`. The values go to `/opt/docker/etc/php/fpm/pool.d/zz-environment.conf`, which PHP-FPM reads after the pool files of the image, see [Configuration files](customization.md#php).

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

The pool uses `pm = dynamic` and runs as `application`. `FPM_PROCESS_IDLE_TIMEOUT` applies only with `fpm.pool.pm: ondemand`; with `dynamic`, PHP-FPM keeps between `FPM_PM_MIN_SPARE_SERVERS` and `FPM_PM_MAX_SPARE_SERVERS` idle workers instead.

## Uploads and long requests

Images: `php-*`; the web server limits apply to `php-nginx` and `php-apache` and their `-dev` variants.

### Uploads

Three limits apply to a request with an upload. Raise them together:

| Variable | Default | Limit | When it is exceeded |
| --- | --- | --- | --- |
| `SERVICE_NGINX_CLIENT_MAX_BODY_SIZE` | `50m` | Request body in Nginx (`client_max_body_size`) | Nginx answers `413 Request Entity Too Large`; PHP never sees the request. |
| `PHP_POST_MAX_SIZE` | `50M` | Whole request body in PHP (`post_max_size`) | PHP discards the body: the script runs with empty `$_POST` and `$_FILES`. |
| `PHP_UPLOAD_MAX_FILESIZE` | `50M` | Each uploaded file (`upload_max_filesize`) | The file is not stored; its `error` in `$_FILES` is `1` (`UPLOAD_ERR_INI_SIZE`). |

Apache's own limit, `LimitRequestBody` (1 GiB by default), applies only to files Apache serves itself, not to requests it passes to PHP-FPM, even if you set it. In `php-apache`, only the two PHP limits apply to PHP scripts.

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    environment:
      SERVICE_NGINX_CLIENT_MAX_BODY_SIZE: 100m
      PHP_POST_MAX_SIZE: 100M
      PHP_UPLOAD_MAX_FILESIZE: 100M
```

### Long requests

Three timeouts end a long request:

| Variable | Default | Limit | When it is exceeded |
| --- | --- | --- | --- |
| `WEB_PHP_TIMEOUT` | `600` | Seconds the web server waits for PHP-FPM | Nginx and Apache answer `504 Gateway Time-out`. |
| `FPM_REQUEST_TERMINATE_TIMEOUT` | `0` (no limit) | Seconds PHP-FPM lets a request run (`request_terminate_timeout`) | PHP-FPM kills the process. Nginx answers `502 Bad Gateway`, Apache `503 Service Unavailable`. |
| `PHP_MAX_EXECUTION_TIME` | `300` | Seconds a PHP script may run (`max_execution_time`) | PHP stops the script with a fatal error, status `500`. Only the time PHP itself runs counts: time spent waiting, for example in `sleep()`, does not. In the `8.6` tags, waiting counts as well, see [PHP 8.6](images/php.md#php-86). The PHP CLI has no limit. |

For long requests, raise `PHP_MAX_EXECUTION_TIME` if PHP itself runs longer than five minutes (in the `8.6` tags: if the request takes longer than five minutes), and `WEB_PHP_TIMEOUT` if the whole request takes longer than ten:

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    environment:
      PHP_MAX_EXECUTION_TIME: "1800"
      WEB_PHP_TIMEOUT: "1800"
```

## Development images

Images: `php-dev`, `php-nginx-dev`, `php-apache-dev`.

| Variable | Default | Effect |
| --- | --- | --- |
| `PHP_DEBUGGER` | not set (Xdebug) | Debugger extension to load: `xdebug`, `blackfire` or `none`. With `blackfire` and both `BLACKFIRE_SERVER_ID` and `BLACKFIRE_SERVER_TOKEN` set, supervisord also runs the Blackfire agent of the Blackfire CLI (`blackfire agent:start`) as the service `blackfire-agent`, and the extension sends its profiles to it over `/var/run/blackfire/agent.sock`. Without the two, the agent is not started; to use an agent in a container of its own, point the extension at it with `php.blackfire.agent_socket`, for example `tcp://blackfire:8307`. |
| `XDEBUG_MODE` | `debug` | `xdebug.mode` |
| `XDEBUG_START_WITH_REQUEST` | `trigger` | `xdebug.start_with_request` |
| `XDEBUG_CLIENT_HOST` | `localhost` | `xdebug.client_host` |
| `XDEBUG_CLIENT_PORT` | `9003` | `xdebug.client_port` |
| `XDEBUG_DISCOVER_CLIENT_HOST` | `1` | `xdebug.discover_client_host` |
| `XDEBUG_TRIGGER_VALUE` | empty | `xdebug.trigger_value` |
| `XDEBUG_OUTPUT_DIR` | `/tmp/debug` | `xdebug.output_dir` |
| `BLACKFIRE_SERVER_ID` | not set | `blackfire.server_id`, and the server ID of the Blackfire agent; used with `PHP_DEBUGGER=blackfire` |
| `BLACKFIRE_SERVER_TOKEN` | not set | `blackfire.server_token`, and the server token of the Blackfire agent; used with `PHP_DEBUGGER=blackfire` |

Each `XDEBUG_*` variable you set is written at start to `98-webdevops.ini`, after the image's Xdebug defaults and the `php.*` variables, so it wins over both. Your own `php.ini` is loaded after it and wins over the variables, see [Configuration files](customization.md#php).

## Services

Each service reads its options when it starts. A service only runs if it is enabled; see [Customization](customization.md#services).

| Variable | Effect |
| --- | --- |
| `SERVICE_SUPERVISOR_OPTS` | Extra arguments for `supervisord`, for example `--loglevel=debug`. |
| `SERVICE_SYSLOG_OPTS` | Extra arguments for `syslog-ng`. |
| `SERVICE_CRON_OPTS` | Extra arguments for `cron -f` (Debian) or `crond -f` (Alpine). |
| `SERVICE_SSH_OPTS` | Extra arguments for `sshd -D -e`. With `-e`, sshd logs to its stderr, see [Logs](customization.md#logs). |
| `SERVICE_DNSMASQ_OPTS` | Extra arguments for `dnsmasq`. |
| `SERVICE_DNSMASQ_USER` | User `dnsmasq` runs as. Default: `root`. |
| `SERVICE_NGINX_OPTS` | Extra arguments for `nginx`, appended after `-g 'daemon off;'`. |
| `SERVICE_APACHE_OPTS` | Extra arguments for Apache, for example `-DMYFLAG`. |
| `SERVICE_POSTFIX_OPTS` | Options for the `postfix` command, given before `start`, `stop` and `reload`, for example `-v`: the `postfix` command then logs in more detail while it starts and stops Postfix; the mail daemons keep their log level. The messages reach the container log, on Debian through syslog. If Postfix does not start, for example because of an unknown option, supervisord gives up after four attempts and the container log shows `postfix <options> start failed`. |
| `SERVICE_PHPFPM_OPTS` | Extra arguments for `php-fpm`, for example `-dmax_input_time=120`. |
| `SERVICE_BLACKFIRE_AGENT_OPTS` | Extra arguments for `blackfire agent:start`, for example `--socket=tcp://0.0.0.0:8307`. Only in the development images, where the agent runs with `PHP_DEBUGGER=blackfire` and the server credentials, see [Development images](#development-images). If the agent does not start, for example because of an unknown option, supervisord gives up after four attempts. |
| `POSTFIX_RELAYHOST` | Postfix `relayhost`, for example `[mail.example.com]:25`. The relay host must accept mail from the container without a login; there is no variable for one. See [Mail](images/php.md#mail). |
| `POSTFIX_MYNETWORKS` | Postfix `mynetworks`. Other Postfix settings have no variable; set them with `postconf -e`, for example `postconf -e "message_size_limit = 52428800"`, in a [service hook](customization.md#service-hooks) in `/opt/docker/bin/service.d/postfix.d/`. |
| `VIRTUAL_HOST` | Comma-separated host names. When `dnsmasq` starts, each one is added to its configuration as `address=/<host>/127.0.0.1`; each `*.example.com` becomes `address=/.example.com/127.0.0.1`, a wildcard for all its subdomains. |
