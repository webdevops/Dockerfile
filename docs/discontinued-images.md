# Replacing discontinued images

`webdevops/storage`, `webdevops/vsftp` and `webdevops/ssh` are no longer built. Their last images stay on Docker Hub, but they get no updates, including security fixes. This page shows a replacement for each. The examples are a starting point, not a complete migration. They use the `latest` tag of other projects' images; for production, pin a version or digest, see [Platforms and updates](README.md#platforms-and-updates).

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
