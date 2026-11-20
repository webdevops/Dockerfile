# webdevops/php-nginx

[`webdevops/php`](https://hub.docker.com/r/webdevops/php) plus Nginx. Nginx serves `/app` and passes PHP requests to PHP-FPM in the same container. Ports: `80` (HTTP) and `443` (HTTPS with a self-signed certificate).

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
  app:
    image: webdevops/php-nginx:8.5
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
    volumes:
      - ./:/app
```

## Documentation

- [webdevops/php-nginx and webdevops/php-nginx-dev](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php-nginx.md)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Customization: commands, scripts, services and hooks](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
