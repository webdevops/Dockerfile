# Replacing discontinued images

`webdevops/nginx`, `webdevops/apache`, `webdevops/bootstrap`, `webdevops/base`, `webdevops/base-app`, `webdevops/dockerfile-build-env`, `webdevops/storage`, `webdevops/vsftp` and `webdevops/ssh` are no longer built. Their last images stay on Docker Hub, but they get no updates, including security fixes. `webdevops/php-official` is deprecated and gets no new tags. This page shows a replacement for each. The examples are a starting point, not a complete migration. They use the `latest` tag of other projects' images; for production, pin a version or digest, see [Platforms and updates](README.md#platforms-and-updates).

## webdevops/php-official

`webdevops/php-official` is the same image as `webdevops/php`: the CI builds it once and publishes it under both names, with the same digest. The second name is only published for the tags `8.2` to `8.5` and `8.2-alpine` to `8.5-alpine`; newer PHP versions, starting with 8.6, are only published as `webdevops/php`.

Switch to `webdevops/php` now. For the tags `8.2` to `8.5`, change only the image name and keep the tag:

```yaml
services:
  app:
    # before: webdevops/php-official:8.5
    image: webdevops/php:8.5
```

The older tags of `webdevops/php-official`, `5.6` to `8.1`, are no longer built under either name and get no updates; switch to `webdevops/php` with a PHP version that is still built, see the [tags of `webdevops/php`](images/php.md#tags).

## webdevops/nginx

The last image, `alpine`, has Nginx 1.30 on Alpine 3.24. The other tags are older: `latest` is Ubuntu 18.04 with Nginx 1.14.0 from April 2020. Use the official [`nginx:alpine`](https://hub.docker.com/_/nginx) image. At start, it fills the templates in `/etc/nginx/templates/` with the environment variables, so `WEB_DOCUMENT_ROOT` and `WEB_DOCUMENT_INDEX` can stay:

```yaml
services:
  web:
    image: nginx:alpine
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
      WEB_DOCUMENT_INDEX: index.html
    volumes:
      - ./public:/app/public:ro
      - ./templates:/etc/nginx/templates:ro
```

`templates/default.conf.template`:

```nginx
server {
    listen 80;
    root ${WEB_DOCUMENT_ROOT};
    index ${WEB_DOCUMENT_INDEX};

    location / {
        try_files $uri $uri/ =404;
    }
}
```

| `webdevops/nginx` | `nginx:alpine` |
| --- | --- |
| `WEB_DOCUMENT_ROOT`, `WEB_DOCUMENT_INDEX` | `root` and `index` in the template, as above |
| `WEB_ALIAS_DOMAIN` | `server_name` in the template; without it, the server answers every host name |
| HTTPS on port `443` with a self-signed certificate | not included; add a `listen 443 ssl` server with your certificate, or terminate TLS in a reverse proxy |
| hooks in `/opt/docker/bin/service.d/nginx.d/` | scripts in `/docker-entrypoint.d/`, run at every start |
| `LOG_STDOUT`, `LOG_STDERR` | not needed; the access and error logs go to the stdout and stderr of the container |

## webdevops/apache

The last image, `alpine`, has Apache 2.4 on Alpine 3.24. The other tags are older: `latest` is Ubuntu 18.04 with Apache 2.4.29 from April 2020. Use the official [`httpd:alpine`](https://hub.docker.com/_/httpd) image. Apache reads environment variables in its configuration as `${NAME}`; this Dockerfile uses that for `WEB_DOCUMENT_ROOT` and `WEB_DOCUMENT_INDEX`:

```dockerfile
FROM httpd:alpine
RUN sed -i \
        -e 's#"/usr/local/apache2/htdocs"#"${WEB_DOCUMENT_ROOT}"#g' \
        -e 's#DirectoryIndex index.html#DirectoryIndex ${WEB_DOCUMENT_INDEX}#' \
        /usr/local/apache2/conf/httpd.conf
ENV WEB_DOCUMENT_ROOT=/usr/local/apache2/htdocs \
    WEB_DOCUMENT_INDEX=index.html
```

```yaml
services:
  web:
    build: .
    ports:
      - "8080:80"
    environment:
      WEB_DOCUMENT_ROOT: /app/public
      WEB_DOCUMENT_INDEX: index.html
    volumes:
      - ./public:/app/public:ro
```

| `webdevops/apache` | `httpd:alpine` |
| --- | --- |
| `WEB_DOCUMENT_ROOT`, `WEB_DOCUMENT_INDEX` | as above; without the Dockerfile, mount your files at `/usr/local/apache2/htdocs` |
| HTTPS on port `443` with a self-signed certificate | not included; enable `conf/extra/httpd-ssl.conf` with your certificate, or terminate TLS in a reverse proxy |
| hooks in `/opt/docker/bin/service.d/httpd.d/` | none; change the configuration in your Dockerfile or mount a configuration file |
| `LOG_STDOUT`, `LOG_STDERR` | not needed; the access and error logs go to the stdout and stderr of the container |

## webdevops/bootstrap, webdevops/base and webdevops/base-app

`webdevops/bootstrap`, `webdevops/base` and `webdevops/base-app` were last built on Ubuntu 22.04 (tags `ubuntu-22.04` and `latest`), their `alpine` tags on Alpine 3.24. The PHP images never built on them; they bring the same entrypoint and tools themselves.

For an image with the entrypoint, the services and the tools of `base` and `base-app`, build on a PHP image. `webdevops/php:8.5` (Debian 12) and `webdevops/php:8.5-alpine` have `/entrypoint`, `supervisord`, `docker-service`, `docker-run-bootstrap`, `docker-cronjob`, `apt-install` (Debian) or `apk-install` (Alpine), `go-replace` and `gosu`, the user `application` with `/app`, and the tools of `base-app`, such as `git`, `rsync` and the SSH client. Unlike `base` and `base-app`, they start PHP-FPM, cron and syslog-ng; disable the services you do not need:

```dockerfile
FROM webdevops/php:8.5
RUN docker-service disable php-fpm \
    && docker-service disable cron \
    && docker-service disable syslog
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```

The `cli` command runs `CLI_SCRIPT` as `application` there; `base`, which had no user `application`, ran it as `root`.

For only the helper scripts of `bootstrap`, such as `apt-install` and `docker-run-bootstrap`, copy them from [`webdevops/toolbox`](https://hub.docker.com/r/webdevops/toolbox) into an official image, as `bootstrap` did:

```dockerfile
FROM ubuntu:22.04
COPY --from=webdevops/toolbox /baselayout/sbin/* /sbin/
COPY --from=webdevops/toolbox /baselayout/usr/local/bin/* /usr/local/bin/
COPY docker/bootstrap.d/ /bootstrap.d/
RUN apt-install rsync \
    && docker-run-bootstrap
```

`bootstrap` also set `TERM=xterm`, `LANG=C.UTF-8` and `LC_ALL=C.UTF-8`; add them with `ENV` if you need them.

## webdevops/dockerfile-build-env

`webdevops/dockerfile-build-env` was the container in which the CI of this repository built and tested the images: `webdevops/base-app:ubuntu-22.04` with Docker, git and make. The CI no longer needs it: its jobs run directly on the GitHub-hosted runners `ubuntu-24.04` and `ubuntu-24.04-arm`, which have Docker, Buildx, git and make. To build and test the images on your own machine, you need Docker, git and make, see [Building and testing locally](../CONTRIBUTING.md#building-and-testing-locally).

## webdevops/storage

The last builds were `alpine:latest` without changes. Older builds provided `/storage` as a volume for other containers.

- As a base image or for one-off commands, use `alpine:latest`.
- To keep or share data, use a named volume. The data stays when the container is recreated:

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    volumes:
      - storage:/storage

volumes:
  storage:
```

## webdevops/vsftp

Use another FTP server image, for example [`garethflowers/ftp-server`](https://hub.docker.com/r/garethflowers/ftp-server). It also runs vsftpd and stores uploads in `/home/<user>`:

```yaml
services:
  ftp:
    image: garethflowers/ftp-server
    ports:
      - "20-21:20-21"
      - "40000-40009:40000-40009"
    environment:
      FTP_USER: upload
      FTP_PASS: change-me
      UID: "1000"
      GID: "1000"
      PUBLIC_IP: 127.0.0.1
    volumes:
      - ./ftp:/home/upload
```

| `webdevops/vsftp` | `garethflowers/ftp-server` |
| --- | --- |
| `FTP_USER` | `FTP_USER` |
| `FTP_PASSWORD` | `FTP_PASS` |
| `FTP_UID`, `FTP_GID` | `UID`, `GID` |
| passive ports `12020` to `12025` | passive ports `40000` to `40009` |
| not needed | `PUBLIC_IP`: the address clients connect to for passive transfers; set it to the address of your server |

## webdevops/ssh

`webdevops/ssh` was `webdevops/base-app` with the SSH server enabled. Use another SSH server image, for example [`lscr.io/linuxserver/openssh-server`](https://hub.docker.com/r/linuxserver/openssh-server). This example creates the user `application` with UID and GID `1000` and allows login with the key in `authorized_keys`:

```yaml
services:
  ssh:
    image: lscr.io/linuxserver/openssh-server
    ports:
      - "2222:2222"
    environment:
      USER_NAME: application
      PUID: "1000"
      PGID: "1000"
      PUBLIC_KEY_FILE: /keys/authorized_keys
    volumes:
      - ./authorized_keys:/keys/authorized_keys:ro
```

Connect with `ssh -p 2222 application@localhost`. Password login is off. The user's home directory is `/config`.

| `webdevops/ssh` | `lscr.io/linuxserver/openssh-server` |
| --- | --- |
| user `application`, UID and GID `1000` | `USER_NAME`, `PUID`, `PGID` |
| port `22` | port `2222` |
| key in `/home/application/.ssh/authorized_keys` | `PUBLIC_KEY_FILE` (a mounted file) or `PUBLIC_KEY` (the key itself) |
| tools of `base-app`, such as `git` and `rsync` | not included; add them in your own image |

```dockerfile
FROM lscr.io/linuxserver/openssh-server
RUN apk add --no-cache git rsync
```
