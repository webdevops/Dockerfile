# Apache webserver Docker container

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`           | Predefined CLI script for service
`APPLICATION_UID`      | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`      | PHP-FPM GID (Effective group ID)
`WEB_DOCUMENT_ROOT`    | Document root for Apache HTTPD
`WEB_DOCUMENT_INDEX`   | Document index (eg. `index.php`) for Apache HTTPD
`WEB_ALIAS_DOMAIN`     | Alias domains (eg. `*.vm`) for Apache HTTPD

## Filesystem layout

Directory                       | Description
------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/etc/httpd`         | Apache configuration
`/opt/docker/etc/httpd/ssl`     | Apache ssl configuration with example server.crt, server.csr, server.key

File                                          | Description
--------------------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/etc/httpd/main.conf`             | Main include file (will include `global.conf`, `php.conf` and `vhost.conf`) 
`/opt/docker/etc/httpd/global.conf`           | Global apache configuration options
`/opt/docker/etc/httpd/conf.d/*.conf`         | Global apache configuration directory (will be included)
`/opt/docker/etc/httpd/php.conf`              | PHP configuration (connection to FPM)
`/opt/docker/etc/httpd/vhost.common.d/*.conf` | Vhost common directory (will be included)
`/opt/docker/etc/httpd/vhost.conf`            | Default vhost
`/opt/docker/etc/httpd/vhost.ssl.conf`        | Default ssl configuration for vhost


## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/apache:latest             | [![](https://badge.imagelayers.io/webdevops/apache:latest.svg)](https://imagelayers.io/?images=webdevops/apache:latest 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-14.04       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-15.04       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/apache:ubuntu-15.10       | [![](https://badge.imagelayers.io/webdevops/apache:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/apache:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/apache:centos-7           | [![](https://badge.imagelayers.io/webdevops/apache:centos-7.svg)](https://imagelayers.io/?images=webdevops/apache:centos-7 'Get your own badge on imagelayers.io')
webdevops/apache:debian-7           | [![](https://badge.imagelayers.io/webdevops/apache:debian-7.svg)](https://imagelayers.io/?images=webdevops/apache:debian-7 'Get your own badge on imagelayers.io')
webdevops/apache:debian-8           | [![](https://badge.imagelayers.io/webdevops/apache:debian-8.svg)](https://imagelayers.io/?images=webdevops/apache:debian-8 'Get your own badge on imagelayers.io')
