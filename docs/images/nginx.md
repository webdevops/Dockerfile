# webdevops/nginx

Nginx without PHP, on [`webdevops/base`](base.md).

## Tags

| Tag | Based on |
| --- | --- |
| `alpine` | `webdevops/base:alpine` (Alpine 3.11, Nginx 1.16.1) |

## Usage

```yaml
services:
  web:
    image: webdevops/nginx:alpine
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
      WEB_DOCUMENT_INDEX: index.html
    volumes:
      - ./:/app
```

Ports: `80` and `443`. Started by default: `nginx`.

Settings: [Web server](../environment-variables.md#web-server) (`WEB_PHP_SOCKET` is empty, there is no PHP), hooks in `/opt/docker/bin/service.d/nginx.d`, see [Customization](../customization.md).
