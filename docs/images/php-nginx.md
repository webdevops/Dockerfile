# webdevops/php-nginx and webdevops/php-nginx-dev

[`webdevops/php`](php.md) plus Nginx. Nginx serves `/app` and passes PHP requests to PHP-FPM in the same container.

## Tags

`8.2`, `8.3`, `8.4`, `8.5` (Debian 12), `8.6` (Debian 13) and `8.2-alpine`, `8.3-alpine`, `8.4-alpine`, `8.5-alpine`, `8.6-alpine`, for both `php-nginx` and `php-nginx-dev`. The `8.6` tags are a preview, see [PHP 8.6](php.md#php-86-preview).

## Usage

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
    volumes:
      - ./:/app
```

Ports: `80` (HTTP) and `443` (HTTPS). HTTPS uses a self-signed certificate for `*.vm` that ships with the image; it expired on 2025-05-01. To use your own certificate, see [Configuration files](../customization.md#configuration-files). Nginx listens on IPv4 and IPv6; if the container has no IPv6, it listens on IPv4 only. Your own configuration files with `listen [::]` are not changed, see [Configuration files](../customization.md#configuration-files).

## Services

Started by default: `nginx`, `php-fpm`, `cron`, `syslog`. The `-dev` variant also starts `postfix` and `ssh`.

## Configuration

- Web server settings: [Web server](../environment-variables.md#web-server). `WEB_DOCUMENT_ROOT`, `WEB_DOCUMENT_INDEX`, `WEB_ALIAS_DOMAIN`, `WEB_PHP_TIMEOUT` and `SERVICE_NGINX_CLIENT_MAX_BODY_SIZE` apply to Nginx.
- PHP and PHP-FPM settings: see [`webdevops/php`](php.md) and [Environment variables](../environment-variables.md#php).
- Scripts and hooks: [Customization](../customization.md). Nginx's hook directory is `/opt/docker/bin/service.d/nginx.d`.

## Development variant (`php-nginx-dev`)

`php-nginx-dev` is built on `php-nginx` and changes the following:

- Xdebug is loaded by default, as in [`php-dev`](php.md#development-variant-php-dev); `PHP_DEBUGGER` switches to Blackfire or turns both off. To connect your IDE, see [Xdebug with an IDE](php.md#xdebug-with-an-ide).
- `opcache.revalidate_freq` is `0`.
- Postfix and an SSH server are started. Postfix sends mail to real recipients unless you set a relay host, see [Mail](php.md#mail). Unlike `php-dev`, no passwords are set, so nobody can log in over SSH; use `docker compose exec` instead, see [Known issues](../known-issues.md#ssh-in-php-nginx-dev-and-php-apache-dev).
- Files matching `WEB_NO_CACHE_PATTERN` (CSS, JavaScript, images, JSON, XML by default) are served with `expires off`.
