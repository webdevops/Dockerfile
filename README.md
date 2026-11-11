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

How the files in this repository are generated, and how the images are built and tested, is described in [CONTRIBUTING.md](CONTRIBUTING.md).

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
