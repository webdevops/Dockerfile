# webdevops/bootstrap, webdevops/base and webdevops/base-app

The foundation for the non-PHP images. The PHP images do not build on these; they bring the same entrypoint and tools themselves.

| Image | Adds |
| --- | --- |
| `webdevops/bootstrap` | The operating system plus the webdevops helper scripts, for example `docker-run-bootstrap`. |
| `webdevops/base` | Entrypoint, `supervisord`, service scripts and hooks, see [Customization](../customization.md). |
| `webdevops/base-app` | The user and group `application` (UID/GID `1000`) and `/app`. |

## Tags

| Tag | Based on |
| --- | --- |
| `ubuntu-22.04`, `latest` | `ubuntu:22.04` |
| `alpine` | `alpine:3.11` |

The same tags exist for `bootstrap`, `base` and `base-app`.

## Services

No service is started by default. `cron`, `syslog`, `postfix`, `ssh` and `dnsmasq` are prepared; `docker-service enable <name>` installs and enables them, see [Services](../customization.md#services).

## Usage

These images are meant as a base for your own images:

```dockerfile
FROM webdevops/base-app:ubuntu-22.04
COPY docker/bootstrap.d/ /bootstrap.d/
RUN docker-run-bootstrap
```
