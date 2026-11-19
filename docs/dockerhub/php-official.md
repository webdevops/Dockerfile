# webdevops/php-official

Built from the same Dockerfiles as [`webdevops/php`](https://hub.docker.com/r/webdevops/php), with the same tags: PHP-FPM on top of the official PHP image.

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `8.5`, `8.4`, `8.3`, `8.2` | Debian 12 |
| `8.5-alpine`, `8.4-alpine`, `8.3-alpine`, `8.2-alpine` | Alpine |

## Quick start

```yaml
services:
  php:
    image: webdevops/php-official:8.5
    volumes:
      - ./:/app
```

## Documentation

- [webdevops/php and webdevops/php-dev](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Customization: commands, scripts, services and hooks](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
