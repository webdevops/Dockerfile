# PHP container layout

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

Container                           | Distribution name        | PHP Version                                                               
----------------------------------- | -----------------------------------------
`webdevops/php:ubuntu-12.04`        | precise                  | PHP 5.3
`webdevops/php:ubuntu-14.04`        | trusty (LTS)             | PHP 5.5
`webdevops/php:ubuntu-15.04`        | vivid                    | PHP 5.6
`webdevops/php:ubuntu-15.10`        | wily                     | PHP 5.6
`webdevops/php:ubuntu-16.04`        | xenial (LTS)             | PHP 7.0
`webdevops/php:debian-7`            | wheezy                   | PHP 5.4
`webdevops/php:debian-8`            | jessie                   | PHP 5.6
`webdevops/php:debian-8-php7`       | jessie with dotdeb       | PHP 7.x (via dotdeb)
`webdevops/php:centos-7`            |                          | PHP 5.4


## Filesystem layout

The whole docker directroy is deployed into `/opt/docker/`.

File                                                   | Description
------------------------------------------------------ | ------------------------------------------------------------------------------
`/opt/docker/etc/php/fpm/php-fpm.conf`                 | FPM daemon configuration
`/opt/docker/etc/php/fpm/pool.d/application.conf`      | FPM pool configuration


## Environment variables

Variable            | Description
------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`        | Predefined CLI script for service
`APPLICATION_UID`   | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`   | PHP-FPM GID (Effective group ID)

## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/php:latest                | [![](https://badge.imagelayers.io/webdevops/php:latest.svg)](https://imagelayers.io/?images=webdevops/php:latest 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-12.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-14.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-15.04          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/php:ubuntu-15.10          | [![](https://badge.imagelayers.io/webdevops/php:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/php:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/php:centos-7              | [![](https://badge.imagelayers.io/webdevops/php:centos-7.svg)](https://imagelayers.io/?images=webdevops/php:centos-7 'Get your own badge on imagelayers.io')
webdevops/php:debian-8-php7         | [![](https://badge.imagelayers.io/webdevops/php:debian-8-php7.svg)](https://imagelayers.io/?images=webdevops/php:debian-8-php7 'Get your own badge on imagelayers.io')
webdevops/php:debian-8              | [![](https://badge.imagelayers.io/webdevops/php:debian-8.svg)](https://imagelayers.io/?images=webdevops/php:debian-8 'Get your own badge on imagelayers.io')
webdevops/php:debian-7              | [![](https://badge.imagelayers.io/webdevops/php:debian-7.svg)](https://imagelayers.io/?images=webdevops/php:debian-7 'Get your own badge on imagelayers.io')
