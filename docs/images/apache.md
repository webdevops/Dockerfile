# webdevops/apache

Apache without PHP, on [`webdevops/base`](base.md).

## Tags

| Tag | Based on |
| --- | --- |
| `alpine` | `webdevops/base:alpine` (Alpine 3.11, Apache 2.4.51) |

## Usage

```yaml
services:
  web:
    image: webdevops/apache:alpine
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
    volumes:
      - ./:/app
```

The document root must exist: the image does not contain `/app`, and Apache does not start without it.

Ports: `80` and `443`. Started by default: `apache`.

Settings: [Web server](../environment-variables.md#web-server) (`WEB_PHP_SOCKET` is empty, there is no PHP; an existing `index.html` takes precedence over `WEB_DOCUMENT_INDEX`), hooks in `/opt/docker/bin/service.d/httpd.d`, see [Customization](../customization.md).
