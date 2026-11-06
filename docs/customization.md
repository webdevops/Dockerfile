# Customization

All images except `bootstrap`, `storage` and `toolbox` share the same entrypoint (`/entrypoint`), service handling and hooks. This page describes them.

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

## Services

`supervisord` starts every enabled service. The start scripts live in `/opt/docker/bin/service.d/`.

| Image | Started by default | Available, not started |
| --- | --- | --- |
| `php`, `php-nginx`, `php-apache` | `php-fpm`, `cron`, `syslog`, plus `nginx` or `apache` | `postfix`, `ssh`, `dnsmasq` |
| `php-dev`, `php-nginx-dev`, `php-apache-dev` | as above, plus `postfix` and `ssh` | `dnsmasq` |
| `nginx`, `apache` | `nginx` or `apache` | `cron`, `syslog`, `postfix`, `ssh`, `dnsmasq` |
| `vsftp` | `vsftp` | `cron`, `syslog`, `postfix`, `ssh`, `dnsmasq` |
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
| `vsftp` | `/opt/docker/bin/service.d/vsftp.d` |
| `supervisord` itself | `/opt/docker/bin/service.d/supervisor.d` |

Options for each service are set with environment variables, see [Services](environment-variables.md#services).

## Cron jobs

`docker-cronjob` adds a line to `/etc/cron.d/webdevops-docker`. Lines use the system crontab format, including the user:

```dockerfile
FROM webdevops/php:8.5
RUN docker-cronjob '*/5 * * * * application /usr/local/bin/php /app/bin/console app:cleanup'
```

Cron runs jobs with a minimal `PATH` that does not contain `/usr/local/bin`, so use absolute paths such as `/usr/local/bin/php`.

This works in the Debian- and Ubuntu-based images only. Alpine's `crond` does not read `/etc/cron.d`.

## Configuration files

Add your own configuration files at these places. A mounted file or a `COPY` in your Dockerfile both work.

| What | Where |
| --- | --- |
| Nginx, server level | `/opt/docker/etc/nginx/conf.d/*.conf` |
| Nginx, inside the default virtual host | `/opt/docker/etc/nginx/vhost.common.d/*.conf` |
| Apache, server level | `/opt/docker/etc/httpd/conf.d/*.conf` |
| Apache, inside the default virtual host | `/opt/docker/etc/httpd/vhost.common.d/*.conf` |
| PHP settings | `/opt/docker/etc/php/php.ini`, loaded last as `99-docker.ini` |
| Additional `supervisord` programs | `/opt/docker/etc/supervisor.d/*.conf` |
| TLS certificate for port 443 | `server.crt` and `server.key` in `/opt/docker/etc/nginx/ssl/` (Nginx) or `/opt/docker/etc/httpd/ssl/` (Apache) |

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    volumes:
      - ./:/app
      - ./docker/nginx/10-headers.conf:/opt/docker/etc/nginx/vhost.common.d/10-headers.conf:ro
      - ./docker/ssl:/opt/docker/etc/nginx/ssl:ro
```

## Application user

Images based on `base-app`, and all `php-*` images, contain the user and group `application` (UID and GID `1000`) and the directory `/app`, owned by that user. PHP-FPM runs as `application`. Changing `APPLICATION_UID` or `APPLICATION_GID` at runtime does not change the user.

The development images (`php-dev`, `php-nginx-dev`, `php-apache-dev`) start an SSH server. `php-dev` also sets the password `dev` for both `root` and `application`. Use the development images for local development only.
