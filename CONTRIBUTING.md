# Contributing

How the files in this repository are generated, and how the images are built and tested.
How to use the images is described in the [README](README.md) and the [documentation](docs/README.md).

## File generation

The files inside the `docker` directory, the GitHub Actions workflow (`.github/workflows/build.yaml`) and the banner at the top of the [README](README.md) are generated with PHP:

```
make generate
```

It installs the PHP tools of `ci/` with Composer and runs `ci/console generate` in `webdevops/php:8.4-alpine`, so you only need `make` and Docker.
The container runs as your user, so the files it writes belong to you.
`ci/console generate` runs these commands in this order; each of them also runs on its own:

| Command | Writes |
| --- | --- |
| `ci/console generate:dockerfile` | `docker/<image>/<tag>/Dockerfile` from the `Dockerfile.twig` next to it and the Twig templates in the `template` directory, and the links `docker/php/<version>` to `docker/php-official/<version>` |
| `ci/console generate:provision` | `docker/<image>/<tag>/conf/` from the `provisioning` directory, as configured in `conf/provision.yml` |
| `ci/console generate:github-ci` | `.github/workflows/build.yaml`, a build and a publish job for every Dockerfile |
| `ci/console generate:readme-banner` | `docs/assets/readme-banner-*.svg` |

**The generated files are never modified manually.**
Edit the `Dockerfile.twig` templates, the shared templates in `template`, the files in `provisioning` or the settings in `conf` instead.
`template/Dockerfile/layout.twig` writes the header of every Dockerfile and includes its `Dockerfile.twig`.
A `Dockerfile.twig` imports the macros it uses from `template/Dockerfile`, e.g. `{% import 'docker.twig' as docker %}`; a macro imports the macros it calls itself.
`generate:provision` empties the `conf/` directories of an image first, so a file removed from `provisioning` disappears there too.
It checks all of `conf/provision.yml` before it changes a file and stops if a source directory or a tag does not exist, or if an image has another setting than `configuration`.
`generate:dockerfile` stops if a directory `docker/<image>/<tag>` has files but no `Dockerfile.twig`; it first removes the `.DS_Store` files of the macOS Finder under `docker`.
To stop building a tag, remove its directory and those of the tags that build on it; `generate:github-ci` names any it finds. `generate:dockerfile` adds and removes the links in `docker/php` along with the `php-official` tags.

## Building in CI

The banner (`docs/assets/readme-banner-*.svg`) takes the PHP versions, web servers, base OS suffixes, Debian releases and platforms from the images that are built.
Its texts are in `conf/readme-banner.yml`. The CI fails if a generated file is out of date or missing.

The PHP code in `ci/` follows the rules of [pluswerk/grumphp-config](https://github.com/andersundsehr/grumphp-config) (PSR-12, PHPStan level 8, Rector, `composer audit`) and has PHPUnit tests in `ci/tests`.
GrumPHP runs all of them; after a change to `ci/`, run it with:

```
make check
```

It installs the PHP tools of `ci/` and runs GrumPHP as your user. `make test` runs only the PHPUnit tests.

The CI job "Check ci/" runs the same checks. No image build waits for it.

GitHub Actions builds every image independently and runs the serverspec and structure tests
defined for it before pushing it to Docker Hub. The CI also uses `ghcr.io/webdevops/`, but only
internally; pull the images from Docker Hub.

## Building and testing locally

Build a single image from its directory. Its parent image (the `FROM` line) and `webdevops/toolbox` are taken from your local images or pulled from Docker Hub:

```
docker build -t webdevops/php-nginx:8.5 docker/php-nginx/8.5
```

Build all PHP images of one version (default `8.5`), in the order they depend on each other:

```
./build-local.sh 8.4
```

Then try an image with the [quick start](README.md#quick-start).

The CI runs the serverspec tests for every image except `toolbox` and `dockerfile-build-env`, and the structure tests for `bootstrap`, `base`, `base-app`, `php`, `php-dev` and `php-nginx`.
To run the structure tests against the images you built locally:

```
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v $PWD:/app -w /app webdevops/dockerfile-build-env make structure-test
```

It tests the local tags that the CI builds and fails if no such image exists.

## PHP extension builds

PHP images require Docker BuildKit. The official `ghcr.io/php/pie:bin` image
supplies PIE through a build-only mount; the existing INI configuration still
controls extension loading. PIE requests remain unpinned, and compilation uses
`nproc` parallel jobs.

Protobuf, Vips and XML-RPC are built directly from their official upstream sources.
The existing XML-RPC **1.0.0RC3** pin is preserved on all supported PHP versions.
Protobuf on PHP 8.2–8.5 uses the latest upstream release. If a future release raises its minimum PHP
version, older PHP image builds can fail; there is no automatic fallback to an
older release. The existing CI matrix checks compatibility across PHP versions.

The native Vips extension is deprecated and no longer maintained upstream. It is
retained for existing applications, using the upstream default branch (currently
`master`): GitHub tags predate the PECL 1.0.13 release and would downgrade the
extension. This includes changes beyond the last PECL release.
No extension installation needs `pecl.php.net`; Serverspec and container structure
tests retain their existing image matrix.
