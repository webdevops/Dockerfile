# PHP docker images with super powers 🚀

We provide images which extend the official [PHP-Images](https://hub.docker.com/_/php/tags).
We add additional stuff like:

* almost any PHP module preinstalled
* configuration based on ENV variables
* run multiple services (like php-fpm and nginx) with supervisord

[![GitHub issues](https://img.shields.io/github/issues/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/issues)[![GitHub forks](https://img.shields.io/github/forks/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/network)[![GitHub stars](https://img.shields.io/github/stars/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/stargazers)[![GitHub license](https://img.shields.io/github/license/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/blob/master/LICENSE)
[![pipeline status](https://gitlab.com/webdevops/dockerfile/badges/master/pipeline.svg)](https://gitlab.com/webdevops/dockerfile/commits/master)



Automated build and test running on [Gitlab CI](https://gitlab.com/webdevops/dockerfile/) \(GitLab.com CI custom Google Cloud runner\) sponsored by [Onedrop GmbH & Co. KG](https://1drop.de).

[![Docker layout](documentation/gitbook/.gitbook/assets/docker-image-layout.gv.png)](https://github.com/webdevops/Dockerfile/tree/511a870fa90fe53da5c63a95b4254f6980e6d3d2/documentation/docs/resources/images/docker-image-layout.gv.png)

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

**The files in the `docker` directory are never modified manually**

*This will be replaced with PHP twig templates in the future to streamline everything.*

### Building in CI

We generate a multi stage Gitlab-CI configuration using PHP:

```
docker run --rm -ti -v $PWD:/app -w /app/ci webdevops/php:8.4-alpine composer install
docker run --rm -ti -v $PWD:/app -w /app webdevops/php:8.4-alpine ci/console github:generate
```

Gitlab CI builds every image independent and runs serverspec and structure tests on every
image before pushing them to the registry.

### PHP extension builds

PHP images require Docker BuildKit. The official `ghcr.io/php/pie:bin` image
supplies PIE through a build-only mount; the existing INI configuration still
controls extension loading. PIE requests remain unpinned, and compilation uses
`nproc` parallel jobs.

Protobuf, Vips and XML-RPC are built directly from their official upstream sources.
Vips and XML-RPC are only built for PHP 8.2–8.5 (see [PHP 8.6](#php-86-preview)).
The existing XML-RPC **1.0.0RC3** pin is preserved on those versions.
Protobuf on PHP 8.2–8.6 uses the latest upstream release. If a future release raises its minimum PHP
version, older PHP image builds can fail; there is no automatic fallback to an
older release. The existing CI matrix checks compatibility across PHP versions.

The native Vips extension is deprecated and no longer maintained upstream. It is
retained for existing applications, using the upstream default branch (currently
`master`): GitHub tags predate the PECL 1.0.13 release and would downgrade the
extension. This includes changes beyond the last PECL release.
No extension installation needs `pecl.php.net`; Serverspec and container structure
tests retain their existing image matrix.

## Documentation

As in many projects the documentation is kind of up to date 😅.

* [Old version of the documentation is available on readthedocs](https://dockerfile.readthedocs.io/)
* [New version is currently being written on gitbook](https://webdevops.gitbook.io/dockerfile)

### Debian PHP

The following images which are currently supported are based on `php:{VER}-fpm-bookworm`
(PHP 8.6: `php:8.6-rc-fpm-trixie`).

* `webdevops/php:8.2`
* `webdevops/php:8.3`
* `webdevops/php:8.4`
* `webdevops/php:8.5`
* `webdevops/php:8.6` (preview, see below)
* `webdevops/php-dev:8.2`
* `webdevops/php-dev:8.3`
* `webdevops/php-dev:8.4`
* `webdevops/php-dev:8.5`
* `webdevops/php-dev:8.6` (preview, see below)
* `webdevops/php-apache:8.2`
* `webdevops/php-apache:8.3`
* `webdevops/php-apache:8.4`
* `webdevops/php-apache:8.5`
* `webdevops/php-apache:8.6` (preview, see below)
* `webdevops/php-apache-dev:8.2`
* `webdevops/php-apache-dev:8.3`
* `webdevops/php-apache-dev:8.4`
* `webdevops/php-apache-dev:8.5`
* `webdevops/php-apache-dev:8.6` (preview, see below)
* `webdevops/php-nginx:8.2`
* `webdevops/php-nginx:8.3`
* `webdevops/php-nginx:8.4`
* `webdevops/php-nginx:8.5`
* `webdevops/php-nginx:8.6` (preview, see below)
* `webdevops/php-nginx-dev:8.2`
* `webdevops/php-nginx-dev:8.3`
* `webdevops/php-nginx-dev:8.4`
* `webdevops/php-nginx-dev:8.5`
* `webdevops/php-nginx-dev:8.6` (preview, see below)


### PHP 8.6 (preview)

PHP 8.6 images are a preview until the remaining upstream workarounds are gone:

* They are built from the official **release candidate** images (`php:8.6-rc-fpm-trixie`,
  `php:8.6-rc-fpm-alpine`) until PHP 8.6.0 is released.
* The Debian variant uses **Debian 13 (trixie)**: PHP 8.6 requires `libavif >= 1.0.0`
  for GD, which bookworm does not ship. PHP 8.2–8.5 stay on bookworm.
* The official PHP 8.6 images are always built **thread safe (ZTS)** and no longer
  contain `pecl`/`pear` or a built-in `mbstring`; `mbstring` is compiled separately.
* Several extensions do not have a release that supports PHP 8.6 yet. Until they do,
  `apcu`, `excimer`, `memcached`, `redis` and `yaml` are built from their development
  branches, `amqp` from an unmerged upstream fix
  ([php-amqp#638](https://github.com/php-amqp/php-amqp/pull/638)), `protobuf` with a
  one-line compatibility patch and Xdebug from the `3.6` pre-releases.
* The Debian `-dev` images do not contain Tideways yet: `tideways-php` 5.47.0 crashes
  PHP 8.6.0RC4 (ZTS) on startup. The Alpine `-dev` images install the Blackfire probe
  directly, because `blackfire php:install` picks the glibc probe on Alpine with ZTS.

#### Removed in PHP 8.6 (breaking)

The following extensions are still available in the PHP 8.2–8.5 images, but are
**not** part of the PHP 8.6 images:

| Extension | Reason | Migration |
|-----------|--------|-----------|
| `vips` | Deprecated upstream and no longer maintained; does not build against PHP 8.6. | Use [`jcupitt/vips`](https://github.com/libvips/php-vips) 2.x, which calls libvips via FFI. `libvips` and the `ffi` extension remain in the images. For PHP-FPM, enable FFI with `php.ffi.enable=true` (see [PHP images](documentation/gitbook/php-images/php.md)). |
| `xmlrpc` | Removed from PHP core in PHP 8.0 because its library is unmaintained; never had a stable PECL release and does not build against PHP 8.6. | Use a userland XML-RPC library, for example [`phpxmlrpc/phpxmlrpc`](https://github.com/gggeek/phpxmlrpc). |

### Alpine PHP

The following images which are currently supported are based on `php:{VER}-fpm-alpine`.

* `webdevops/php:8.2-alpine`
* `webdevops/php:8.3-alpine`
* `webdevops/php:8.4-alpine`
* `webdevops/php:8.5-alpine`
* `webdevops/php:8.6-alpine` (preview, see below)
* `webdevops/php-dev:8.2-alpine`
* `webdevops/php-dev:8.3-alpine`
* `webdevops/php-dev:8.4-alpine`
* `webdevops/php-dev:8.5-alpine`
* `webdevops/php-dev:8.6-alpine` (preview, see below)
* `webdevops/php-apache:8.2-alpine`
* `webdevops/php-apache:8.3-alpine`
* `webdevops/php-apache:8.4-alpine`
* `webdevops/php-apache:8.5-alpine`
* `webdevops/php-apache:8.6-alpine` (preview, see below)
* `webdevops/php-apache-dev:8.2-alpine`
* `webdevops/php-apache-dev:8.3-alpine`
* `webdevops/php-apache-dev:8.4-alpine`
* `webdevops/php-apache-dev:8.5-alpine`
* `webdevops/php-apache-dev:8.6-alpine` (preview, see below)
* `webdevops/php-nginx:8.2-alpine`
* `webdevops/php-nginx:8.3-alpine`
* `webdevops/php-nginx:8.4-alpine`
* `webdevops/php-nginx:8.5-alpine`
* `webdevops/php-nginx:8.6-alpine` (preview, see below)
* `webdevops/php-nginx-dev:8.2-alpine`
* `webdevops/php-nginx-dev:8.3-alpine`
* `webdevops/php-nginx-dev:8.4-alpine`
* `webdevops/php-nginx-dev:8.5-alpine`
* `webdevops/php-nginx-dev:8.6-alpine` (preview, see below)
