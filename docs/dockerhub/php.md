# webdevops/php

PHP-FPM on top of the official PHP image, with the webdevops entrypoint, `supervisord`, cron, syslog-ng and the `application` user. PHP-FPM listens on port `9000`. At every start, PHP-FPM sizes its pool of workers to the CPU and memory limits of the container, or to the host without limits, see [Size of the PHP-FPM pool](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md#size-of-the-php-fpm-pool). For a web server in the same container, use [`webdevops/php-nginx`](https://hub.docker.com/r/webdevops/php-nginx) or [`webdevops/php-apache`](https://hub.docker.com/r/webdevops/php-apache).

Always use one of the tags below: an image without a tag (`latest`) and the `alpine` tag are old builds from 2019 and 2020 that get no updates, see [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md#latest-and-alpine-tags-of-the-php-images).

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `8.6` | Debian 13 |
| `8.5`, `8.4`, `8.3`, `8.2` | Debian 12 |
| `8.6-alpine`, `8.5-alpine`, `8.4-alpine`, `8.3-alpine`, `8.2-alpine` | Alpine |

Tags that are not listed here are no longer built and get no updates.

## Quick start

```yaml
services:
  php:
    image: webdevops/php:8.5
    volumes:
      - ./:/app
```

## Documentation

- [webdevops/php and webdevops/php-dev](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Customization: commands, scripts, services and hooks](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
