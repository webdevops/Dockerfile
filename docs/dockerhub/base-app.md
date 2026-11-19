# webdevops/base-app

[`webdevops/base`](https://hub.docker.com/r/webdevops/base) plus the user and group `application` (UID/GID `1000`), `/app` and common tools: `zip`, `unzip`, `bzip2`, `git`, `patch`, `rsync`, the SSH client (`ssh`), a DNS lookup tool (`dig`) and `moreutils`. A base for your own images.

The `alpine` tag is no longer built, see [Replacing discontinued images](https://github.com/webdevops/Dockerfile/blob/master/docs/discontinued-images.md#alpine-tags-of-bootstrap-base-and-base-app).

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `ubuntu-22.04`, `latest` | Ubuntu 22.04 |

## Quick start

```dockerfile
FROM webdevops/base-app:ubuntu-22.04
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```

## Documentation

- [webdevops/bootstrap, webdevops/base and webdevops/base-app](https://github.com/webdevops/Dockerfile/blob/master/docs/images/base.md)
- [Customization: commands, scripts, services and hooks](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md)
- [Environment variables](https://github.com/webdevops/Dockerfile/blob/master/docs/environment-variables.md)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
