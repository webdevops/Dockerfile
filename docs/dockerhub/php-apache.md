# webdevops/php-apache

[`webdevops/php`](https://hub.docker.com/r/webdevops/php) plus Apache. Apache serves `/app` and passes PHP requests to PHP-FPM in the same container. Ports: `80` (HTTP) and `443` (HTTPS with a self-signed certificate).

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `8.5`, `8.4`, `8.3`, `8.2` | Debian 12 |
| `8.5-alpine`, `8.4-alpine`, `8.3-alpine`, `8.2-alpine` | Alpine |

## Quick start

```yaml
services:
  app:
    image: webdevops/php-apache:8.5
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
    volumes:
      - ./:/app
```

## Documentation

- [webdevops/php-apache and webdevops/php-apache-dev](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php-apache.md)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Customization: commands, scripts, services and hooks](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
