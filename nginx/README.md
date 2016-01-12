# Nginx webserver Docker container

## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`           | Predefined CLI script for service
`APPLICATION_UID`      | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`      | PHP-FPM GID (Effective group ID)
`WEB_DOCUMENT_ROOT`    | Document root for Nginx
`WEB_DOCUMENT_INDEX`   | Document index (eg. `index.php`) for Nginx
`WEB_ALIAS_DOMAIN`     | Alias domains (eg. `*.vm`) for Nginx

## Filesystem layout

Directory                       | Description
------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/etc/nginx`         | Nginx configuration
`/opt/docker/etc/nginx/ssl`     | Nginx ssl configuration with example server.crt, server.csr, server.key

File                                      | Description
----------------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/etc/nginx/main.conf`         | Main include file (will include `global.conf`, `php.conf` and `vhost.conf`) 
`/opt/docker/etc/nginx/global.conf`       | Global nginx configuration options
`/opt/docker/etc/nginx/php.conf`          | PHP configuration (connection to FPM)
`/opt/docker/etc/nginx/vhost.common.conf` | Vhost common stuff (placeholder)
`/opt/docker/etc/nginx/vhost.conf`        | Default vhost
`/opt/docker/etc/nginx/vhost.ssl.conf`    | Default ssl configuration for vhost


## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/nginx:latest              | [![](https://badge.imagelayers.io/webdevops/nginx:latest.svg)](https://imagelayers.io/?images=webdevops/nginx:latest 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-14.04        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-15.04        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/nginx:ubuntu-15.10        | [![](https://badge.imagelayers.io/webdevops/nginx:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/nginx:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/nginx:centos-7            | [![](https://badge.imagelayers.io/webdevops/nginx:centos-7.svg)](https://imagelayers.io/?images=webdevops/nginx:centos-7 'Get your own badge on imagelayers.io')
webdevops/nginx:debian-7            | [![](https://badge.imagelayers.io/webdevops/nginx:debian-7.svg)](https://imagelayers.io/?images=webdevops/nginx:debian-7 'Get your own badge on imagelayers.io')
webdevops/nginx:debian-8            | [![](https://badge.imagelayers.io/webdevops/nginx:debian-8.svg)](https://imagelayers.io/?images=webdevops/nginx:debian-8 'Get your own badge on imagelayers.io')
