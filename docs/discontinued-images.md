# Replacing discontinued images

`webdevops/storage`, `webdevops/vsftp` and `webdevops/ssh` are no longer built. Their last images stay on Docker Hub, but they get no updates, including security fixes. This page shows a replacement for each. It covers the common cases, not every setup.

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

`webdevops/ssh` was [`webdevops/base-app`](images/base.md) with the SSH server enabled. Build the same image yourself:

```dockerfile
FROM webdevops/base-app:ubuntu-22.04
RUN docker-service enable ssh
EXPOSE 22
```

```yaml
services:
  ssh:
    build: .
    ports:
      - "2222:22"
    volumes:
      - ./authorized_keys:/home/application/.ssh/authorized_keys:ro
```

Neither `root` nor `application` has a password, so log in with a key: put your public key into `authorized_keys` and connect with `ssh -p 2222 application@localhost`. The container has the tools of `base-app`, for example `git` and `rsync`.
