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

Images: `base-app`, `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `APPLICATION_USER` | `application` | User the `cli` entrypoint runs `CLI_SCRIPT` as; if it is empty, `application`. |
| `APPLICATION_GROUP` | `application` | Information only. The group is created when the image is built; changing the variable has no effect. |
| `APPLICATION_UID` | `1000` | Information only: UID of the `application` user. To change it, see [Other UID or GID](customization.md#other-uid-or-gid). |
| `APPLICATION_GID` | `1000` | Information only: GID of the `application` group. To change it, see [Other UID or GID](customization.md#other-uid-or-gid). |
| `APPLICATION_PATH` | `/app` | Information only: `/app` exists and belongs to `application`. |

## Container

Images: `base`, `base-app`, `php-*`.

| Variable | Default | Effect |
| --- | --- | --- |
| `CLI_SCRIPT` | not set | Command the `cli` entrypoint runs as `APPLICATION_USER`, in `base` as `root`. See [Customization](customization.md#commands). |
| `LOG_STDOUT` | container stdout | File that `/docker.stdout` points to. Nginx, Apache and PHP-FPM write their access logs there, syslog-ng the messages of the services, for example of cron and Postfix. |
| `LOG_STDERR` | container stderr | File that `/docker.stderr` points to. Nginx and Apache write their error logs there, PHP-FPM its own messages and the errors of the PHP scripts it runs. `php.error_log` gives the PHP errors of PHP-FPM and the PHP CLI a file of their own instead, in a directory that `application` may write to, for example `/app`. |
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
| `PHP_OPCACHE_MAX_ACCELERATED_FILES` | `opcache.max_accelerated_files` | `32531` |
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
| `FPM_PM_MAX_CHILDREN` | `pm.max_children` | computed, see [Size of the PHP-FPM pool](#size-of-the-php-fpm-pool) |
| `FPM_PM_START_SERVERS` | `pm.start_servers` | computed |
| `FPM_PM_MIN_SPARE_SERVERS` | `pm.min_spare_servers` | computed |
| `FPM_PM_MAX_SPARE_SERVERS` | `pm.max_spare_servers` | computed |
| `FPM_PROCESS_IDLE_TIMEOUT` | `pm.process_idle_timeout` | `10s` |
| `FPM_MAX_REQUESTS` | `pm.max_requests` | `1000`: a worker ends after 1000 requests and a new one takes its place, so that memory that leaks in an extension or the application does not add up. `0` keeps the workers. |
| `FPM_REQUEST_TERMINATE_TIMEOUT` | `request_terminate_timeout` | `0` |
| `FPM_RLIMIT_FILES` | `rlimit_files` | `0` |
| `FPM_RLIMIT_CORE` | `rlimit_core` | `0` |

The pool uses `pm = dynamic` and runs as `application`. `FPM_PROCESS_IDLE_TIMEOUT` applies only with `fpm.pool.pm: ondemand`; with `dynamic`, PHP-FPM keeps between `FPM_PM_MIN_SPARE_SERVERS` and `FPM_PM_MAX_SPARE_SERVERS` idle workers instead.

### Size of the PHP-FPM pool

At every start, the entrypoint sizes the pool to the CPUs and the memory of the container:

| Directive | Value |
| --- | --- |
| `pm.max_children` | The smaller of two numbers, at least `2`: the workers of `FPM_AUTO_CHILD_MEMORY` that fit into the memory, less `PHP_OPCACHE_MEMORY_CONSUMPTION` for OPcache and 256 MB for the web server, cron and the other processes; and `FPM_AUTO_CHILDREN_PER_CPU` workers per CPU. |
| `pm.min_spare_servers` | Half the CPUs, at least `1` |
| `pm.max_spare_servers` | Two per CPU, at most `pm.max_children` |
| `pm.start_servers` | Halfway between the two, as PHP-FPM computes it by default |

The CPUs are the CPU limit of the container (`cpus`), rounded up, and at most the CPUs it may run on (`cpuset`); without a limit, the CPUs of the host. The memory is the memory limit of the container (`mem_limit`); without one, the memory of the host. Both cgroup v2 and cgroup v1 limits count. For example:

| Container | `pm.max_children` | `pm.start_servers` | `pm.min_spare_servers` | `pm.max_spare_servers` |
| --- | --- | --- | --- | --- |
| `cpus: 1`, `mem_limit: 512m` | `2` | `1` | `1` | `2` |
| `cpus: 2`, `mem_limit: 1g` | `4` | `2` | `1` | `4` |
| `cpus: 2`, `mem_limit: 2g` | `8` | `2` | `1` | `4` |
| no limits, on a host with 4 CPUs and 16 GB | `16` | `5` | `2` | `8` |

The container log shows the result at start, for example `PHP-FPM: 2 CPUs (cgroup v2 limit), 1024 MB memory (cgroup v2 limit) -> pm.max_children=4, pm.start_servers=2, pm.min_spare_servers=1, pm.max_spare_servers=4`.

| Variable | Default | Effect |
| --- | --- | --- |
| `FPM_AUTO_SIZE` | `1` | `0` switches the sizing off. The pool then keeps the values of the official PHP image: `pm.max_children` `5`, `pm.start_servers` `2`, `pm.min_spare_servers` `1`, `pm.max_spare_servers` `3`. |
| `FPM_AUTO_CHILD_MEMORY` | `128M` | Memory of one worker, in megabytes (`M`) or gigabytes (`G`). OPcache is shared by all workers and counted once, see above. `memory_limit` (`512M`) is the most a single request may use, not what a worker usually needs. |
| `FPM_AUTO_CHILDREN_PER_CPU` | `4` | Workers per CPU. A PHP request usually spends most of its time waiting for the database or other services; lower it for requests that mostly compute. |

A value set with `FPM_PM_*` or `fpm.pool.pm.*` stays as set, and the others are computed to fit it: with `cpus: 2` and `mem_limit: 1g`, `FPM_PM_MAX_CHILDREN: "7"` keeps `pm.max_spare_servers` at `4`, `FPM_PM_MAX_CHILDREN: "3"` lowers it to `3`. If one of your pool files sets `pm`, `pm.max_children`, `pm.start_servers`, `pm.min_spare_servers` or `pm.max_spare_servers` to another value than the image, for example an `application.conf` or a `www.conf` of your own, nothing is computed and the values of your pool files apply; the container log then says `PHP-FPM: a pool file sets pm.* to other values than the image, so the pool is not sized to the container`.

The sizing does not change `pm`. With `fpm.pool.pm: static`, PHP-FPM starts all `pm.max_children` workers at once.

Without limits, every container sizes itself to the whole host. With `pm = dynamic`, PHP-FPM starts more workers only under load, but several busy containers on one host can then use more memory than the host has. In production, set `cpus` and `mem_limit` for each container, see [Production](images/php.md#production).

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
| `SERVICE_CRON_OPTS` | Extra arguments for `cron -f` (Debian/Ubuntu) or `crond -f` (Alpine). |
| `SERVICE_SSH_OPTS` | Extra arguments for `sshd -D`. |
| `SERVICE_DNSMASQ_OPTS` | Extra arguments for `dnsmasq`. |
| `SERVICE_DNSMASQ_USER` | User `dnsmasq` runs as. Default: `root`. |
| `SERVICE_NGINX_OPTS` | Extra arguments for `nginx`, appended after `-g 'daemon off;'`. |
| `SERVICE_APACHE_OPTS` | Extra arguments for Apache, for example `-DMYFLAG`. |
| `SERVICE_POSTFIX_OPTS` | Options for the `postfix` command, given before `start`, `stop` and `reload`, for example `-v`: the `postfix` command then logs in more detail while it starts and stops Postfix; the mail daemons keep their log level. The messages reach the container log, on Debian and Ubuntu through syslog. If Postfix does not start, for example because of an unknown option, supervisord gives up after four attempts and the container log shows `postfix <options> start failed`. |
| `SERVICE_PHPFPM_OPTS` | Extra arguments for `php-fpm`, for example `-dmax_input_time=120`. |
| `SERVICE_BLACKFIRE_AGENT_OPTS` | Extra arguments for `blackfire agent:start`, for example `--socket=tcp://0.0.0.0:8307`. Only in the development images, where the agent runs with `PHP_DEBUGGER=blackfire` and the server credentials, see [Development images](#development-images). If the agent does not start, for example because of an unknown option, supervisord gives up after four attempts. |
| `POSTFIX_RELAYHOST` | Postfix `relayhost`, for example `[mail.example.com]:25`. The relay host must accept mail from the container without a login; there is no variable for one. See [Mail](images/php.md#mail). |
| `POSTFIX_MYNETWORKS` | Postfix `mynetworks`. Other Postfix settings have no variable; set them with `postconf -e`, for example `postconf -e "message_size_limit = 52428800"`, in a [service hook](customization.md#service-hooks) in `/opt/docker/bin/service.d/postfix.d/`. |
| `VIRTUAL_HOST` | Comma-separated host names. When `dnsmasq` starts, each one is added to its configuration as `address=/<host>/127.0.0.1`; each `*.example.com` becomes `address=/.example.com/127.0.0.1`, a wildcard for all its subdomains. |
