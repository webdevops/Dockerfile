# webdevops/php-apache-dev

[`webdevops/php-apache`](https://hub.docker.com/r/webdevops/php-apache) for local development. Xdebug is loaded by default, as in [`webdevops/php-dev`](https://hub.docker.com/r/webdevops/php-dev). Postfix and an SSH server are started, and `root` and `application` have the password `dev`.

Meant for local development. Do not use it in production.

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
    image: webdevops/php-apache-dev:8.5
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
    volumes:
      - ./:/app
```

## Documentation

- [Development variant](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php-apache.md#development-variant-php-apache-dev)
- [Xdebug with an IDE](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md#xdebug-with-an-ide)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
