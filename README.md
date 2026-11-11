<picture>
  <source media="(prefers-color-scheme: dark)" srcset="docs/assets/readme-banner-dark.svg">
  <img src="docs/assets/readme-banner-light.svg" alt="How an image tag is built: web server, variant, PHP version and base OS, with all options of each part">
</picture>

# PHP docker images with super powers 🚀

We provide images which extend the official [PHP-Images](https://hub.docker.com/_/php/tags).
We add additional stuff like:

* almost any PHP module preinstalled
* configuration based on ENV variables
* run multiple services (like php-fpm and nginx) with supervisord

[![GitHub issues](https://img.shields.io/github/issues/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/issues)[![GitHub forks](https://img.shields.io/github/forks/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/network)[![GitHub stars](https://img.shields.io/github/stars/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/stargazers)[![GitHub license](https://img.shields.io/github/license/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/blob/master/LICENSE)
[![build](https://github.com/webdevops/Dockerfile/actions/workflows/build.yaml/badge.svg)](https://github.com/webdevops/Dockerfile/actions/workflows/build.yaml)

## Quick start

Save this as `compose.yaml` in your project (with the entry point in `public/index.php`) and run `docker compose up -d`:

```yaml
services:
  app:
    image: webdevops/php-nginx:8.5
    ports:
      - "8080:80"
    volumes:
      - ./:/app
    environment:
      WEB_DOCUMENT_ROOT: /app/public
```

The application is then available at http://localhost:8080. All images, settings and hooks are described in the [documentation](docs/README.md).

The images run on `linux/amd64` and `linux/arm64` and are rebuilt every night, so a tag such as `8.5` gets new images daily. To pin an image, see [Platforms and updates](docs/README.md#platforms-and-updates).

## Communication and support

Found a bug or have a question? Write an issue in our [GitHub repository](https://github.com/webdevops/Dockerfile/issues). Problems we already know about are listed in [Known issues](docs/known-issues.md).

Or join our discord https://discord.gg/BpBZtXAgJz

## Build process

### File generation

The files inside the `docker` directory, the GitHub Actions workflow (`.github/workflows/build.yaml`) and the banner at the top of this README are generated with PHP:

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

### Building in CI

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

### Building and testing locally

Build a single image from its directory. Its parent image (the `FROM` line) and `webdevops/toolbox` are taken from your local images or pulled from Docker Hub:

```
docker build -t webdevops/php-nginx:8.5 docker/php-nginx/8.5
```

Build all PHP images of one version (default `8.5`), in the order they depend on each other:

```
./build-local.sh 8.4
```

Then try an image with the quick start above.

The CI runs the serverspec tests for every image except `toolbox` and `dockerfile-build-env`, and the structure tests for `bootstrap`, `base`, `base-app`, `php`, `php-dev` and `php-nginx`.
To run the structure tests against the images you built locally:

```
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v $PWD:/app -w /app webdevops/dockerfile-build-env make structure-test
```

It tests the local tags that the CI builds and fails if no such image exists.

### PHP extension builds

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

## Documentation

* [Overview of all images](docs/README.md)
* [Environment variables](docs/environment-variables.md)
* [Customization: commands, hooks, services](docs/customization.md)
* PHP images: [Debian or Alpine](docs/images/php.md#debian-or-alpine), [production](docs/images/php.md#production), [Xdebug with an IDE](docs/images/php.md#xdebug-with-an-ide), [mail](docs/images/php.md#mail)
* [Known issues](docs/known-issues.md)

### Debian PHP

The following images which are currently supported are based on `php:{VER}-fpm-bookworm`.

* `webdevops/php:8.2`
* `webdevops/php:8.3`
* `webdevops/php:8.4`
* `webdevops/php:8.5`
* `webdevops/php-dev:8.2`
* `webdevops/php-dev:8.3`
* `webdevops/php-dev:8.4`
* `webdevops/php-dev:8.5`
* `webdevops/php-apache:8.2`
* `webdevops/php-apache:8.3`
* `webdevops/php-apache:8.4`
* `webdevops/php-apache:8.5`
* `webdevops/php-apache-dev:8.2`
* `webdevops/php-apache-dev:8.3`
* `webdevops/php-apache-dev:8.4`
* `webdevops/php-apache-dev:8.5`
* `webdevops/php-nginx:8.2`
* `webdevops/php-nginx:8.3`
* `webdevops/php-nginx:8.4`
* `webdevops/php-nginx:8.5`
* `webdevops/php-nginx-dev:8.2`
* `webdevops/php-nginx-dev:8.3`
* `webdevops/php-nginx-dev:8.4`
* `webdevops/php-nginx-dev:8.5`


### Alpine PHP

The following images which are currently supported are based on `php:{VER}-fpm-alpine`.

* `webdevops/php:8.2-alpine`
* `webdevops/php:8.3-alpine`
* `webdevops/php:8.4-alpine`
* `webdevops/php:8.5-alpine`
* `webdevops/php-dev:8.2-alpine`
* `webdevops/php-dev:8.3-alpine`
* `webdevops/php-dev:8.4-alpine`
* `webdevops/php-dev:8.5-alpine`
* `webdevops/php-apache:8.2-alpine`
* `webdevops/php-apache:8.3-alpine`
* `webdevops/php-apache:8.4-alpine`
* `webdevops/php-apache:8.5-alpine`
* `webdevops/php-apache-dev:8.2-alpine`
* `webdevops/php-apache-dev:8.3-alpine`
* `webdevops/php-apache-dev:8.4-alpine`
* `webdevops/php-apache-dev:8.5-alpine`
* `webdevops/php-nginx:8.2-alpine`
* `webdevops/php-nginx:8.3-alpine`
* `webdevops/php-nginx:8.4-alpine`
* `webdevops/php-nginx:8.5-alpine`
* `webdevops/php-nginx-dev:8.2-alpine`
* `webdevops/php-nginx-dev:8.3-alpine`
* `webdevops/php-nginx-dev:8.4-alpine`
* `webdevops/php-nginx-dev:8.5-alpine`
