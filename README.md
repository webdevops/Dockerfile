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

## Communication and support

Or write an issue in our [GitHub repository](https://github.com/webdevops/Dockerfile/issues).

Or join our discord https://discord.gg/BpBZtXAgJz

## Build process

### File generation

The general build process is currently a mixture of python jinja2 legacy and PHP.

First we build the files inside the `docker` directory using this command:

```
docker run --rm -ti -v $PWD:/app -w /app webdevops/dockerfile-build-env make provision
``` 

It will use the Jinja2 templates inside of the `template` directory and the 
config files from the `provisioning` directory.

**The generated files in the `docker` directory (`Dockerfile` and `conf/`) are never modified manually.**
Edit the `Dockerfile.jinja2` templates next to them, the shared templates in `template` or the files in `provisioning` instead.
`make provision` copies the `provisioning` files into the `conf/` directories as configured in `conf/provision.yml`.

*This will be replaced with PHP twig templates in the future to streamline everything.*

### Building in CI

We generate the GitHub Actions workflow (`.github/workflows/build.yaml`) using PHP:

```
docker run --rm -ti -v $PWD:/app -w /app/ci webdevops/php:8.4-alpine composer install
docker run --rm -ti -v $PWD:/app -w /app webdevops/php:8.4-alpine ci/console github:generate-ci
```

GitHub Actions builds every image independently and runs serverspec and structure tests on every
image before pushing it to the registry.

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
