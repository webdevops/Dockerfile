# Customization

All images except `bootstrap` and `toolbox` share the same entrypoint (`/entrypoint`), service handling and hooks. This page describes them.

## Commands

The first argument of the container command selects what the entrypoint does:

| Command | What happens |
| --- | --- |
| `supervisord` | Default command. Starts `supervisord`, which runs all enabled [services](#services). |
| `noop` | Keeps the container running without starting services (`tail -f /dev/null`). |
| `root` | Runs the remaining arguments as `root`; without arguments it opens `bash`. |
| `cli` | Runs `CLI_SCRIPT` as `APPLICATION_USER` (default `application`); the remaining arguments are passed on. Only in `base-app` and `php-*` images; elsewhere it fails because `APPLICATION_USER` is not set. |
| anything else (`default`) | Runs the command as given, as `root`. |

```yaml
services:
  console:
    image: webdevops/php:8.5
    environment:
      CLI_SCRIPT: php /app/bin/console
    command: ["cli", "cache:clear"]
    volumes:
      - ./:/app
```

You can add your own command: put `<name>.sh` into `/opt/docker/bin/entrypoint.d/` and start the container with `<name>` as command.

## Scripts at container start

Before the command runs, the entrypoint sources every `*.sh` file in these directories, as `root`:

1. `/opt/docker/provision/entrypoint.d/` (the image's own scripts live here too)
2. `/entrypoint.d/`

This runs on every start and for every command. Mount a directory to add your own scripts:

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    volumes:
      - ./docker/entrypoint.d:/entrypoint.d:ro
```

## Scripts at image build

`docker-run-bootstrap` sources every `*.sh` file in these directories once and then deletes it:

- `/opt/docker/provision/bootstrap.d/`
- `/opt/docker/provision/build.d/`
- `/bootstrap.d/`
- `/build.d/`

Use it in your own Dockerfile:

```dockerfile
FROM webdevops/php-nginx:8.5
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```

## Tools for your Dockerfile

These commands are installed in the images. Use them in `RUN` lines of your own Dockerfile or in your start scripts.

| Command | What it does | Images |
| --- | --- | --- |
| `apt-install <packages>` | Runs `apt-get update`, installs the packages without recommended packages and removes the package lists again | Debian- and Ubuntu-based images, including `bootstrap:ubuntu-22.04` |
| `apk-install <packages>` | Installs packages with `apk add --no-cache` | Alpine-based images, including `bootstrap:alpine` |
| `docker-image-cleanup` | Removes package lists and caches; use it at the end of a `RUN` that installed packages | all except `toolbox`; fails in the Alpine `php-*` images, see [Known issues](known-issues.md#docker-image-cleanup-in-the-alpine-php-images) |
| `go-replace -s <search> -r <replace> -- <file>` | Replaces text in files; the same tool fills the [placeholders](#placeholders) | all |
| `gosu <user> <command>` | Runs a command as another user, for example `application` | all except `toolbox` |
| `docker-service enable <service>`, `docker-service disable <service>`, also `docker-service-enable` and `docker-service-disable` | Enables or disables a [service](#services); enabling installs it if needed | all except `bootstrap` and `toolbox` |
| `docker-cronjob '<line>'` | Adds a [cron job](#cron-jobs) | Debian- and Ubuntu-based images except `bootstrap` |
| `docker-run-bootstrap` | Runs the [build scripts](#scripts-at-image-build) once and deletes them | all except `toolbox` |

`apt-install` and `apk-install` are both present in every image except `toolbox`, but each only works on its own distribution. `toolbox` only stores the commands for the other images, see [Images](README.md#images).

```dockerfile
FROM webdevops/php-nginx:8.5
RUN apt-install jq \
    && docker-image-cleanup
RUN docker-service enable postfix
RUN docker-cronjob '*/5 * * * * application /usr/local/bin/php /app/bin/console app:cleanup'
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```

On Alpine, use `apk-install`, and leave out `docker-cronjob` and, in the PHP images, `docker-image-cleanup`; `apk-install` keeps no package cache anyway:

```dockerfile
FROM webdevops/php-nginx:8.5-alpine
RUN apk-install jq
```

`gosu` and `go-replace` also help in [start scripts](#scripts-at-container-start):

```sh
gosu application /usr/local/bin/php /app/bin/console cache:warmup
go-replace -s '<API_URL>' -r "$API_URL" -- /app/config/app.ini
```

## Services

`supervisord` starts every enabled service. The start scripts live in `/opt/docker/bin/service.d/`.

| Image | Started by default | Available, not started |
| --- | --- | --- |
| `php`, `php-nginx`, `php-apache` | `php-fpm`, `cron`, `syslog`, plus `nginx` or `apache` | `postfix`, `ssh`, `dnsmasq` |
| `php-dev`, `php-nginx-dev`, `php-apache-dev` | as above, plus `postfix` and `ssh` | `dnsmasq` |
| `nginx`, `apache` | `nginx` or `apache` | `cron`, `syslog`, `postfix`, `ssh`, `dnsmasq` |
| `base`, `base-app` | none | `cron`, `syslog`, `postfix`, `ssh`, `dnsmasq` |

Enable or disable a service with `docker-service` (or the shortcuts `docker-service-enable` and `docker-service-disable`). If the service's program is not installed yet, enabling it installs it. Do this in your Dockerfile:

```dockerfile
FROM webdevops/php-nginx:8.5
RUN docker-service enable postfix
RUN docker-service disable cron
```

When a service is enabled for the first time, `docker-service` sources `/opt/docker/provision/service.d/<service>.sh` once; these scripts install the service.

### Service hooks

Before a service starts, its start script sources every `*.sh` file in `/opt/docker/bin/service.d/<name>.d/`:

| Service | Hook directory |
| --- | --- |
| `php-fpm` | `/opt/docker/bin/service.d/php-fpm.d` |
| `nginx` | `/opt/docker/bin/service.d/nginx.d` |
| `apache` | `/opt/docker/bin/service.d/httpd.d` |
| `cron` | `/opt/docker/bin/service.d/cron.d` |
| `syslog` | `/opt/docker/bin/service.d/syslog-ng.d` |
| `postfix` | `/opt/docker/bin/service.d/postfix.d` |
| `ssh` | `/opt/docker/bin/service.d/ssh.d` |
| `dnsmasq` | `/opt/docker/bin/service.d/dnsmasq.d` |
| `supervisord` itself | `/opt/docker/bin/service.d/supervisor.d` |

Options for each service are set with environment variables, see [Services](environment-variables.md#services).

## Cron jobs

`docker-cronjob` adds a line to `/etc/cron.d/webdevops-docker`. Lines use the system crontab format, including the user:

```dockerfile
FROM webdevops/php:8.5
RUN docker-cronjob '*/5 * * * * application /usr/local/bin/php /app/bin/console app:cleanup'
```

Cron runs jobs with a minimal `PATH` that does not contain `/usr/local/bin`, so use absolute paths such as `/usr/local/bin/php`.

This works in the Debian- and Ubuntu-based images only. Alpine's `crond` does not read `/etc/cron.d`; see [Known issues](known-issues.md#docker-cronjob-on-alpine) for the Alpine way.

### Time zone

Cron uses the container's time zone, which is UTC. `PHP_DATE_TIMEZONE` only changes PHP's time zone, not when cron runs a job. To run jobs in local time, set `TZ`. It also changes the output of `date` and the time stamps supervisord writes to the container log. PHP keeps `PHP_DATE_TIMEZONE`, so set both:

```yaml
services:
  app:
    image: webdevops/php:8.5
    environment:
      TZ: Europe/Berlin
      PHP_DATE_TIMEZONE: Europe/Berlin
```

This works in all images, based on Debian, Ubuntu or Alpine.

## Healthcheck

The images define no `HEALTHCHECK`. Add one in `compose.yaml`.

For `php-nginx` and `php-apache`, request a page through the web server. If the page is a PHP script, this also checks PHP-FPM:

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    volumes:
      - ./:/app
    healthcheck:
      test: ["CMD", "curl", "-fsS", "-o", "/dev/null", "http://localhost/"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 10s
```

`curl -f` fails on status `400` and higher, so use a URL that answers `200`, for example a health endpoint of your application.

For `php`, which runs PHP-FPM without a web server, check that PHP-FPM accepts connections on port `9000`:

```yaml
services:
  php:
    image: webdevops/php:8.5
    volumes:
      - ./:/app
    healthcheck:
      test: ["CMD", "php", "-r", "exit(@fsockopen('127.0.0.1', 9000) ? 0 : 1);"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 10s
```

`docker compose ps` shows the result as `healthy` or `unhealthy`.

## Configuration files

The images keep their web server, PHP and supervisord configuration in `/opt/docker/etc/`. To change a shipped file, replace it with your own file of the same name. To add settings, put a new file into one of the include directories. Both a `COPY` in your Dockerfile and a mounted file work; see [Placeholders](#placeholders) for the one limit of mounted files.

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    volumes:
      - ./:/app
      - ./docker/nginx/10-headers.conf:/opt/docker/etc/nginx/vhost.common.d/10-headers.conf:ro
      - ./docker/ssl:/opt/docker/etc/nginx/ssl:ro
```

### Nginx

Images: `php-nginx`, `php-nginx-dev`, `nginx`. The distribution's configuration loads `main.conf` through `/etc/nginx/conf.d/10-docker.conf`. Files in `/opt/docker/etc/nginx/`:

| File | Content |
| --- | --- |
| `main.conf` | Includes, in this order: `global.conf`, `php.conf`, `conf.d/*.conf`, `vhost.conf` |
| `global.conf` | Empty; for your settings at `http` level |
| `conf.d/*.conf` | Settings at `http` level. `conf.d/10-php.conf` defines the upstream `php` (`WEB_PHP_SOCKET`). |
| `vhost.conf` | The default server for port 80 and port 443: `server_name`, `root`, `index`. Both include `vhost.common.d/*.conf`; port 443 also includes `vhost.ssl.conf`. |
| `vhost.ssl.conf` | TLS protocols, ciphers and the paths of `ssl/server.crt` and `ssl/server.key` |
| `vhost.common.d/10-general.conf` | `client_max_body_size` (`SERVICE_NGINX_CLIENT_MAX_BODY_SIZE`) |
| `vhost.common.d/10-location-root.conf` | `location /` with `try_files $uri $uri/ /<DOCUMENT_INDEX>?$query_string` |
| `vhost.common.d/10-log.conf` | Access log to `/docker.stdout`, error log to `/docker.stderr` |
| `vhost.common.d/10-php.conf` | `location ~ \.php$`, passes requests to PHP-FPM (`WEB_PHP_TIMEOUT`) |
| `nginx.conf` | Alpine only: the main `nginx.conf`; `/etc/nginx/nginx.conf` links to it |
| `php.conf` | Leftover, empty. `main.conf` still includes it. |
| `vhost.common.conf` | Leftover, not included anywhere |

### Apache

Images: `php-apache`, `php-apache-dev`, `apache`. The distribution's configuration loads `main.conf` through `/etc/apache2/sites-enabled/10-docker.conf` (Debian) or `/etc/apache2/conf.d/zzz-docker.conf` (Alpine). Files in `/opt/docker/etc/httpd/`:

| File | Content |
| --- | --- |
| `main.conf` | Includes, in this order: `global.conf`, `php.conf`, `conf.d/*.conf`, `vhost.conf` |
| `global.conf` | Empty; for your settings at server level |
| `conf.d/10-error-document.conf` | Short plain-text error pages for static files (images, CSS, JavaScript and similar) |
| `conf.d/10-log.conf` | Log format; access log to `/docker.stdout`, error log to `/docker.stderr` |
| `conf.d/10-php.conf` | Passes `*.php` to PHP-FPM (`WEB_PHP_SOCKET`, `WEB_PHP_TIMEOUT`) |
| `conf.d/10-server.conf` | `ServerName`, `DirectoryIndex`, `DocumentRoot` and its `<Directory>` block with `AllowOverride All` |
| `vhost.conf` | The virtual hosts for port 80 and port 443. Both include `vhost.common.d/*.conf`; port 443 also includes `vhost.ssl.conf`. |
| `vhost.ssl.conf` | TLS settings and the paths of `ssl/server.crt` and `ssl/server.key` |
| `vhost.common.d/01-boilerplate.conf` | Empty; for your settings inside both virtual hosts |
| `php.conf` | Leftover, empty. `main.conf` still includes it. |
| `vhost.common.conf` | Leftover, not included anywhere |

### Placeholders

When the web server's configuration is prepared at container start, these placeholders are replaced in every `*.conf` file below `/opt/docker/etc/nginx/` or `/opt/docker/etc/httpd/`, including subdirectories and your own files:

| Placeholder | Value |
| --- | --- |
| `<DOCUMENT_ROOT>` | `WEB_DOCUMENT_ROOT` |
| `<DOCUMENT_INDEX>` | `WEB_DOCUMENT_INDEX` |
| `<ALIAS_DOMAIN>` | `WEB_ALIAS_DOMAIN` |
| `<SERVERNAME>` | the container's host name |
| `<PHP_SOCKET>` | `WEB_PHP_SOCKET` |
| `<PHP_TIMEOUT>` | `WEB_PHP_TIMEOUT` |
| `<SERVICE_NGINX_CLIENT_MAX_BODY_SIZE>` | `SERVICE_NGINX_CLIENT_MAX_BODY_SIZE` (Nginx only) |

Files with other extensions are left alone. The files are rewritten in place, which matters for mounted files:

- A file without placeholders can be mounted `:ro`.
- A file with placeholders must not be mounted `:ro`: the file cannot be rewritten, and the container exits with code 2 at start.
- A file with placeholders that is mounted read-write is rewritten on the host as well; after the first start it contains the values instead of the placeholders.
- If the container has no IPv6, the `listen [::]` lines are removed from the Nginx files at start. In a file mounted `:ro` they cannot be removed, and Nginx does not start; leave them out of such a file.

Use `COPY` in a Dockerfile for files with placeholders.

If `WEB_PHP_SOCKET` is empty, the files that connect the web server to PHP-FPM are deleted at start: `conf.d/10-php.conf` and `vhost.common.d/10-php.conf` (Nginx), `conf.d/10-php.conf` (Apache). This is the default in `nginx` and `apache`.

### PHP

Images: `php-*`. Files in `/opt/docker/etc/php/`:

| File | Content |
| --- | --- |
| `php.webdevops.ini` | The image's PHP defaults, followed by the values of the `php.*` and `PHP_*` variables. Loaded as `98-webdevops.ini`. At every start it is reset to the image's version and the variables are written again, so do not replace or edit it. |
| `php.ini` | For your settings, empty by default. Loaded as `99-docker.ini`, after `98-webdevops.ini`, so its values win over the environment variables. A `:ro` mount works, except in the development images when one of the [`XDEBUG_*` variables](environment-variables.md#development-images) is set: these images write the Xdebug settings into this file, and with `:ro` the container exits at start. |
| `fpm/php-fpm.conf` | PHP-FPM main configuration; `FPM_PROCESS_MAX` and `fpm.global.*` are appended at every start. |
| `fpm/pool.d/application.conf` | The PHP-FPM pool `[www]`; the `FPM_*` and `fpm.pool.*` values are appended at every start. To replace it, use `COPY` in a Dockerfile: with a `:ro` mount the container exits at start, and a read-write mount gets the values appended in the file on the host. |

`PHP_SENDMAIL_PATH` is the exception to the order above. It is also written into the pool as `php_admin_value[sendmail_path]`, and it is always set (default `/usr/sbin/sendmail -t -i`). So for requests handled by PHP-FPM, `sendmail_path` in your `php.ini` has no effect; only the PHP CLI uses it. Set `PHP_SENDMAIL_PATH` instead.

### supervisord

All images except `bootstrap` and `toolbox`.

| File | Content |
| --- | --- |
| `/opt/docker/etc/supervisor.conf` | Main configuration; includes `supervisor.d/*.conf` |
| `/opt/docker/etc/supervisor.d/<service>.conf` | One group per service with one program, for example the group `nginx` with the program `nginxd` |

Add your own program as a new file in `supervisor.d/`. In a running container, `supervisorctl` controls the services. Address a program as `<group>:<program>`, or all programs of a group as `<group>:`; `supervisorctl restart nginx` fails with `no such process`.

```sh
docker compose exec app supervisorctl status
docker compose exec app supervisorctl restart nginx:nginxd
```

## Locales

| Image | Locales |
| --- | --- |
| `base-app:ubuntu-22.04` and the Debian-based `php-*` images | several hundred locales, for example `de_DE.utf8` and `en_US.utf8` |
| `base:ubuntu-22.04` | only `C`, `C.utf8` and `POSIX` |
| Alpine-based images | none; they have no `locale` command |

List them with `docker compose exec app locale -a`.

## Application user

Images based on `base-app`, and all `php-*` images, contain the user and group `application` (UID and GID `1000`) and the directory `/app`, owned by that user. PHP-FPM runs as `application`.

`docker compose exec` runs commands as `root`. Files they create belong to `root`, on the host too when `/app` is mounted from there. Run commands that write files as `application`:

```sh
docker compose exec -u application app composer install
```

The development images (`php-dev`, `php-nginx-dev`, `php-apache-dev`) start an SSH server. `php-dev` also sets the password `dev` for both `root` and `application`. Use the development images for local development only.

### Other UID or GID

Setting `APPLICATION_UID` or `APPLICATION_GID` in a container does not change the user. If the files on your host belong to another UID than `1000`, change the user in your own image:

```dockerfile
FROM webdevops/php-nginx:8.5
ARG APPLICATION_UID=1000
ARG APPLICATION_GID=1000
RUN groupmod -g "$APPLICATION_GID" application \
    && usermod -u "$APPLICATION_UID" -g "$APPLICATION_GID" application \
    && chown -R application:application /app /home/application
```

```yaml
services:
  app:
    build:
      context: .
      args:
        APPLICATION_UID: "1001"
        APPLICATION_GID: "1001"
    volumes:
      - ./:/app
```

Use the output of `id -u` and `id -g` on your host. PHP-FPM, `docker compose exec -u application` and the `cli` command then run with these IDs. `usermod` and `groupmod` leave `/app` with UID and GID `1000` and the files in `/home/application` with GID `1000`; the `chown` fixes that. This works in the Debian and the Alpine images.
