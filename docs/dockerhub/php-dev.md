# webdevops/php-dev

[`webdevops/php`](https://hub.docker.com/r/webdevops/php) for local development. Xdebug is loaded by default; `PHP_DEBUGGER` switches to Blackfire or turns both off. Postfix and an SSH server are started, and `root` and `application` have the password `dev`.

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
  php:
    image: webdevops/php-dev:8.5
    volumes:
      - ./:/app
```

## Documentation

- [Development variant](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md#development-variant-php-dev)
- [Xdebug with an IDE](https://github.com/webdevops/Dockerfile/blob/master/docs/images/php.md#xdebug-with-an-ide)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
