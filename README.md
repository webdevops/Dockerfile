# Introduction

[![GitHub issues](https://img.shields.io/github/issues/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/issues)[![GitHub forks](https://img.shields.io/github/forks/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/network)[![GitHub stars](https://img.shields.io/github/stars/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/stargazers)[![GitHub license](https://img.shields.io/github/license/webdevops/Dockerfile.svg)](https://github.com/webdevops/Dockerfile/blob/master/LICENSE)
[![pipeline status](https://gitlab.com/webdevops/autobuild/badges/master/pipeline.svg)](https://gitlab.com/webdevops/autobuild/commits/master)

![Dockerfile](https://static.webdevops.io/dockerfile.svg)

Automated build and test running on [WebDevOps CI](https://gitlab.com/webdevops/autobuild/) \(GitLab.com CI custom AWS runner\) sponsored by [Onedrop GmbH & Co. KG](https://1drop.de).

[![Docker layout](documentation/gitbook/.gitbook/assets/docker-image-layout.gv.png)](https://github.com/webdevops/Dockerfile/tree/511a870fa90fe53da5c63a95b4254f6980e6d3d2/documentation/docs/resources/images/docker-image-layout.gv.png)

## Communication and support

Join us on [Slack](https://webdevops.io/slack/)

Or write an issue in our [GitHub repository](https://github.com/webdevops/Dockerfile/issues).

## Documentation

* [Old version of the documentation is available on readthedocs](https://dockerfile.readthedocs.io/)
* [New version is currently being written on gitbook](https://webdevops.gitbook.io/dockerfile)

## Deprecations

### Debian/Ubuntu PHP

The following images are DEPRECATED and not longer built automatically:

* `webdevops/php:ubuntu-*`
* `webdevops/php:debian-*`
* `webdevops/php-dev:ubuntu-*`
* `webdevops/php-dev:debian-*`
* `webdevops/php-apache:ubuntu-*`
* `webdevops/php-apache:debian-*`
* `webdevops/php-apache-dev:ubuntu-*`
* `webdevops/php-apache-dev:debian-*`
* `webdevops/php-nginx:ubuntu-*`
* `webdevops/php-nginx:debian-*`
* `webdevops/php-nginx-dev:ubuntu-*`
* `webdevops/php-nginx-dev:debian-*`

You shall use the new images which are based on the official `php:7.(1|2|3|4)-fpm` images.
The official PHP images are based on debian.

* `webdevops/php:5.6`
* `webdevops/php:7.0`
* `webdevops/php:7.1`
* `webdevops/php:7.2`
* `webdevops/php:7.3`
* `webdevops/php:7.4`
* `webdevops/php-dev:5.6`
* `webdevops/php-dev:7.0`
* `webdevops/php-dev:7.1`
* `webdevops/php-dev:7.2`
* `webdevops/php-dev:7.3`
* `webdevops/php-dev:7.4`
* `webdevops/php-apache:5.6`
* `webdevops/php-apache:7.0`
* `webdevops/php-apache:7.1`
* `webdevops/php-apache:7.2`
* `webdevops/php-apache:7.3`
* `webdevops/php-apache:7.4`
* `webdevops/php-apache-dev:5.6`
* `webdevops/php-apache-dev:7.0`
* `webdevops/php-apache-dev:7.1`
* `webdevops/php-apache-dev:7.2`
* `webdevops/php-apache-dev:7.3`
* `webdevops/php-apache-dev:7.4`
* `webdevops/php-nginx:5.6`
* `webdevops/php-nginx:7.0`
* `webdevops/php-nginx:7.1`
* `webdevops/php-nginx:7.2`
* `webdevops/php-nginx:7.3`
* `webdevops/php-nginx:7.4`
* `webdevops/php-nginx-dev:5.6`
* `webdevops/php-nginx-dev:7.0`
* `webdevops/php-nginx-dev:7.1`
* `webdevops/php-nginx-dev:7.2`
* `webdevops/php-nginx-dev:7.3`
* `webdevops/php-nginx-dev:7.4`


### Alpine PHP

The following images are DEPRECATED and not longer built automatically:

* `webdevops/php:alpine`
* `webdevops/php:alpine-3`
* `webdevops/php:alpine-3-php7`
* `webdevops/php-dev:alpine`
* `webdevops/php-dev:alpine-3`
* `webdevops/php-dev:alpine-3-php7`
* `webdevops/php-apache:alpine`
* `webdevops/php-apache:alpine-3`
* `webdevops/php-apache:alpine-3-php7`
* `webdevops/php-apache-dev:alpine`
* `webdevops/php-apache-dev:alpine-3`
* `webdevops/php-apache-dev:alpine-3-php7`
* `webdevops/php-nginx:alpine`
* `webdevops/php-nginx:alpine-3`
* `webdevops/php-nginx:alpine-3-php7`
* `webdevops/php-nginx-dev:alpine`
* `webdevops/php-nginx-dev:alpine-3`
* `webdevops/php-nginx-dev:alpine-3-php7`

You shall use the new images which are based on the official `php:7.(1|2|3|4)-fpm-alpine` images.

* `webdevops/php:7.1-alpine`
* `webdevops/php:7.2-alpine`
* `webdevops/php:7.3-alpine`
* `webdevops/php:7.4-alpine`
* `webdevops/php-dev:7.1-alpine`
* `webdevops/php-dev:7.2-alpine`
* `webdevops/php-dev:7.3-alpine`
* `webdevops/php-dev:7.4-alpine`
* `webdevops/php-apache:7.1-alpine`
* `webdevops/php-apache:7.2-alpine`
* `webdevops/php-apache:7.3-alpine`
* `webdevops/php-apache:7.4-alpine`
* `webdevops/php-apache-dev:7.1-alpine`
* `webdevops/php-apache-dev:7.2-alpine`
* `webdevops/php-apache-dev:7.3-alpine`
* `webdevops/php-apache-dev:7.4-alpine`
* `webdevops/php-nginx:7.1-alpine`
* `webdevops/php-nginx:7.2-alpine`
* `webdevops/php-nginx:7.3-alpine`
* `webdevops/php-nginx:7.4-alpine`
* `webdevops/php-nginx-dev:7.1-alpine`
* `webdevops/php-nginx-dev:7.2-alpine`
* `webdevops/php-nginx-dev:7.3-alpine`
* `webdevops/php-nginx-dev:7.4-alpine`

*We left out 7.0 because it would vary too much from the current versions*
