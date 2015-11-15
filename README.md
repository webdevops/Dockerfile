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
`FORCE=1 make all`          | Force container build (`docker build --no-cache ...`)
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

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
<strong>Base container<strong>      |
webdevops/base:latest               | [![](https://badge.imagelayers.io/webdevops/base:latest.svg)](https://imagelayers.io/?images=webdevops/base:latest 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-12.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-14.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-15.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-15.10         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/base:centos-7             | [![](https://badge.imagelayers.io/webdevops/base:centos-7.svg)](https://imagelayers.io/?images=webdevops/base:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>PHP container<strong>       |
webdevops/php:latest                | [![](https://badge.imagelayers.io/webdevops/php:latest.svg)](https://imagelayers.io/?images=webdevops/php:latest 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-12.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-14.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-15.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-15.10          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/php:centos-7              | [![](https://badge.imagelayers.io/webdevops/php:centos-7.svg)](https://imagelayers.io/?images=webdevops/php:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Apache HTTPD with PHP container<strong>|
webdevops/php-apache:latest         | [![](https://badge.imagelayers.io/webdevops/php-apache:latest.svg)](https://imagelayers.io/?images=webdevops/php-apache:latest 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-12.04   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-14.04   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-15.04   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-15.10   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/php-apache:centos-7       | [![](https://badge.imagelayers.io/webdevops/php-apache:centos-7.svg)](https://imagelayers.io/?images=webdevops/php-apache:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>NGINX with PHP container<strong>|
webdevops/php-nginx:latest          | [![](https://badge.imagelayers.io/webdevops/php-nginx:latest.svg)](https://imagelayers.io/?images=webdevops/php-nginx:latest 'Get your own badge on imagelayers.io')
webdevops/php-nginx:ubuntu-12.04    | [![](https://badge.imagelayers.io/webdevops/php-nginx:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/php-nginx:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/php-nginx:ubuntu-14.04    | [![](https://badge.imagelayers.io/webdevops/php-nginx:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/php-nginx:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/php-nginx:ubuntu-15.04    | [![](https://badge.imagelayers.io/webdevops/php-nginx:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/php-nginx:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/php-nginx:ubuntu-15.10    | [![](https://badge.imagelayers.io/webdevops/php-nginx:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/php-nginx:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/php-nginx:centos-7        | [![](https://badge.imagelayers.io/webdevops/php-nginx:centos-7.svg)](https://imagelayers.io/?images=webdevops/php-nginx:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Apache HTTPD container<strong>|
webdevops/apache:latest             | [![](https://badge.imagelayers.io/webdevops/apache:latest.svg)](https://imagelayers.io/?images=webdevops/apache:latest 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-12.04       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-14.04       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-15.04       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-15.10       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/apache:centos-7           | [![](https://badge.imagelayers.io/webdevops/apache:centos-7.svg)](https://imagelayers.io/?images=webdevops/apache:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Nginx container<strong>     |
webdevops/nginx:latest              | [![](https://badge.imagelayers.io/webdevops/nginx:latest.svg)](https://imagelayers.io/?images=webdevops/nginx:latest 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-12.04        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-14.04        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-15.04        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-15.10        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/nginx:centos-7            | [![](https://badge.imagelayers.io/webdevops/nginx:centos-7.svg)](https://imagelayers.io/?images=webdevops/nginx:centos-7 'Get your own badge on imagelayers.io')
<br>                                |
<strong>HHVM container<strong>      |
webdevops/hhvm:latest               | [![](https://badge.imagelayers.io/webdevops/hhvm:latest.svg)](https://imagelayers.io/?images=webdevops/hhvm:latest 'Get your own badge on imagelayers.io')
webdevops/hhvm:ubuntu-14.04         | [![](https://badge.imagelayers.io/webdevops/hhvm:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/hhvm:ubuntu-14.04 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Apache HTTPD with HHVM container<strong>|
webdevops/hhvm-apache:latest        | [![](https://badge.imagelayers.io/webdevops/hhvm-apache:latest.svg)](https://imagelayers.io/?images=webdevops/hhvm-apache:latest 'Get your own badge on imagelayers.io')
webdevops/hhvm-apache:ubuntu-14.04  | [![](https://badge.imagelayers.io/webdevops/hhvm-apache:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/hhvm-apache:ubuntu-14.04 'Get your own badge on imagelayers.io')
<br>                                |
<strong>NGINX with PHP container<strong>|
webdevops/hhvm-nginx:latest         | [![](https://badge.imagelayers.io/webdevops/hhvm-nginx:latest.svg)](https://imagelayers.io/?images=webdevops/hhvm-nginx:latest 'Get your own badge on imagelayers.io')
webdevops/hhvm-nginx:ubuntu-14.04   | [![](https://badge.imagelayers.io/webdevops/hhvm-nginx:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/hhvm-nginx:ubuntu-14.04 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Service container<strong>   |
webdevops/samson-deployment:latest  | [![](https://badge.imagelayers.io/webdevops/samson-deployment:latest.svg)](https://imagelayers.io/?images=webdevops/samson-deployment:latest 'Get your own badge on imagelayers.io')
webdevops/ssh:latest                | [![](https://badge.imagelayers.io/webdevops/ssh:latest.svg)](https://imagelayers.io/?images=webdevops/ssh:latest 'Get your own badge on imagelayers.io')
webdevops/vsftp:latest              | [![](https://badge.imagelayers.io/webdevops/vsftp:latest.svg)](https://imagelayers.io/?images=webdevops/vsftp:latest 'Get your own badge on imagelayers.io')
<br>                                |
<strong>Misc container<strong>      |
webdevops/storage:latest            | [![](https://badge.imagelayers.io/webdevops/storage:latest.svg)](https://imagelayers.io/?images=webdevops/storage:latest 'Get your own badge on imagelayers.io')