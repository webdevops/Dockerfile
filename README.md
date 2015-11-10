# WebDevOps Dockerfiles

Dockerfiles for various prebuilt docker containers


Dockerfile                  | Description                                                                        | Depends on                                                           |
--------------------------- | ---------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
[`base`](base/README.md)    | Base containers for WebDevOps service containers                                   | official docker files                                                |
[`php`](php/README.md)      | PHP (cli and fpm) service containers                                               | [`webdevops/base`](https://hub.docker.com/r/webdevops/base/)         |
[`vsftp`](vsftp/README.md)  | VSFTP (ftp service) service container                                              | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`storage`](storage/README.md) | Storage (noop) container                                                           | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`ssh`](ssh/README.md)      | SSH service container                                                              | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`samson-deployment`](samson-deployment/README.md) | [Samson](https://github.com/webdevops/samson-deployment) based deployment service  | [`zendesk/samson`](https://hub.docker.com/r/zendesk/samson/)         |


# Building

Lokal building of containers can be done with `make` and `Makefile`:

Command                     | Description                                                                       
--------------------------- | ----------------------------------------------------------------------------------
`make all`                  | Build all containers *fast mode* (parallel building, `FAST=1`)
`FAST=0 make all`           | Build all containers *slow mode* (serial building)
`DEBUG=1 make all`          | Show log of build process even if process is successfull
<br>                        |
`make base`                 | Build all base containers
`make service`              | Build all service containers
`make php`                  | Build all php containers
`make hhvm`                 | Build all hhvm containers
`make nginx`                | Build all nginx containers
`make apache`               | Build all apache containers
`make webdevops/php-nginx`  | Build specific containers (as example)

# Provisioning

All `base` inherited containers provides an modular provisioning available as simple shell scripts and ansible roles.
See [base/README.md](base/README.md) for more informations.

# Images

* webdevops/base:latest [![](https://badge.imagelayers.io/webdevops/base:latest.svg)](https://imagelayers.io/?images=webdevops/base:latest 'Get your own badge on imagelayers.io')
* webdevops/base:ubuntu-12.04 [![](https://badge.imagelayers.io/webdevops/base:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-12.04 'Get your own badge on imagelayers.io')
* webdevops/base:ubuntu-14.04 [![](https://badge.imagelayers.io/webdevops/base:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-14.04 'Get your own badge on imagelayers.io')
* webdevops/base:ubuntu-15.04 [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.04 'Get your own badge on imagelayers.io')
* webdevops/base:ubuntu-15.10 [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.14 'Get your own badge on imagelayers.io')
* webdevops/base:centos-7 [![](https://badge.imagelayers.io/webdevops/base:centos-7.svg)](https://imagelayers.io/?images=webdevops/base:centos-7 'Get your own badge on imagelayers.io')
