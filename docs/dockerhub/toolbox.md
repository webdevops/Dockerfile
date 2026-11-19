# webdevops/toolbox

Build helper, not meant to run. Stores the baselayout scripts (`apt-install`, `apk-install`, `docker-image-cleanup`, `docker-run-bootstrap`, `generate-locales` and more), `go-replace` and `gosu` under `/baselayout`. [`webdevops/bootstrap`](https://hub.docker.com/r/webdevops/bootstrap) and [`webdevops/php`](https://hub.docker.com/r/webdevops/php) copy them from there with `COPY --from=webdevops/toolbox`. In `toolbox` itself only `go-replace` is on the `PATH`.

Rebuilt every night for linux/amd64 and linux/arm64.

## Tags

| Tag | Based on |
| --- | --- |
| `latest` | Alpine |

## Documentation

- [Images](https://github.com/webdevops/Dockerfile/blob/master/docs/README.md#images)
- [How the images build on each other](https://github.com/webdevops/Dockerfile/blob/master/docs/README.md#how-the-images-build-on-each-other)

Source and issues: [github.com/webdevops/Dockerfile](https://github.com/webdevops/Dockerfile)
