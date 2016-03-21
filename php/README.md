# PHP container layout

Automatic build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

Container                           | Distribution name        | PHP Version                                                               
----------------------------------- | -----------------------------------------
`webdevops/php:ubuntu-12.04`        | precise                  | PHP 5.3
`webdevops/php:ubuntu-14.04`        | trusty (LTS)             | PHP 5.5
`webdevops/php:ubuntu-15.04`        | vivid                    | PHP 5.6
`webdevops/php:ubuntu-15.10`        | wily                     | PHP 5.6
`webdevops/php:ubuntu-16.04`        | xenial (LTS)             | PHP 5.6
`webdevops/php:ubuntu-16.04-php7`   | xenial (LTS)             | PHP 7.0
`webdevops/php:debian-7`            | wheezy                   | PHP 5.4
`webdevops/php:debian-8`            | jessie                   | PHP 5.6
`webdevops/php:debian-8-php7`       | jessie with dotdeb       | PHP 7.x (via dotdeb)
`webdevops/php:centos-7`            |                          | PHP 5.4
`webdevops/php:centos-7-php7`       |                          | PHP 7.0.3 (via webtatic)


## Filesystem layout

The whole docker directroy is deployed into `/opt/docker/`.

File                                                   | Description
------------------------------------------------------ | ------------------------------------------------------------------------------
`/opt/docker/etc/php/fpm/php-fpm.conf`                 | FPM daemon configuration
`/opt/docker/etc/php/fpm/pool.d/application.conf`      | FPM pool configuration
`/opt/docker/etc/oracle/tnsnames.ora`                  | SQL configuration file that defines databases addresses for Oracle ( available for centos-7-php7)


## Environment variables

Variable            | Description
------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`        | Predefined CLI script for service
`APPLICATION_UID`   | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`   | PHP-FPM GID (Effective group ID)
`ORACLE_HOME`       | Contains the directory of the full Oracle Database software.
`LD_LIBRARY_PATH`   | Set this (or its platform equivalent, such as DYLD_LIBRARY_PATH, LIBPATH, or SHLIB_PATH) to the location of the Oracle libraries,
`NLS_LANG`          | This is the primary variable for setting the character set and globalization information used by the Oracle libraries.
`NLS_NUMERIC_CHARACTERS` |
`NLS_DATE_FORMAT`   |
`TNS_ADMIN`         | Contains the directory where the Oracle Net Services configuration files such as tnsnames.ora and sqlnet.ora are kept.
`ORACLE_SID`        | Contains the name of the database on the local machine to be connected to. 

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
