# webdevops/nginx

Nginx without PHP, on [`webdevops/base`](base.md).

## Tags

| Tag | Based on |
| --- | --- |
| `alpine` | `webdevops/base:alpine` (Alpine 3.11, Nginx 1.16.1) |

Alpine 3.11 has had no security updates since November 2021, see [Known issues](../known-issues.md#alpine-311).

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

Ports: `80` (HTTP) and `443` (HTTPS). HTTPS uses a self-signed certificate for `*.vm` that ships with the image; it expired on 2025-05-01. To use your own certificate, see [Configuration files](../customization.md#configuration-files). Started by default: `nginx`. Nginx listens on IPv4 and IPv6; if the container has no IPv6, it listens on IPv4 only. Your own configuration files with `listen [::]` are not changed, see [Configuration files](../customization.md#configuration-files).

Settings: [Web server](../environment-variables.md#web-server) (`WEB_PHP_SOCKET` is empty, there is no PHP), hooks in `/opt/docker/bin/service.d/nginx.d`, see [Customization](../customization.md).
