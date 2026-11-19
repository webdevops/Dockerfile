# webdevops/bootstrap

The operating system plus the webdevops helper scripts, for example `docker-run-bootstrap`. A base for your own images and for [`webdevops/base`](https://hub.docker.com/r/webdevops/base).

The `alpine` tag is no longer built, see [Replacing discontinued images](https://github.com/webdevops/Dockerfile/blob/master/docs/discontinued-images.md#alpine-tags-of-bootstrap-base-and-base-app).

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `ubuntu-22.04`, `latest` | Ubuntu 22.04 |

Tags that are not listed here are no longer built and get no updates.

## Quick start

```dockerfile
FROM webdevops/bootstrap:ubuntu-22.04
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```

## Documentation

- [webdevops/bootstrap, webdevops/base and webdevops/base-app](https://github.com/webdevops/Dockerfile/blob/master/docs/images/base.md)
- [Scripts at image build](https://github.com/webdevops/Dockerfile/blob/master/docs/customization.md#scripts-at-image-build)
- [Known issues](https://github.com/webdevops/Dockerfile/blob/master/docs/known-issues.md)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
