# PHP with Apache container layout

Container                             | Distribution name        | PHP Version                                                               
------------------------------------- | -----------------------------------------
`webdevops/php-apache:ubuntu-12.04`   | precise                  | PHP 5.3
`webdevops/php-apache:ubuntu-14.04`   | trusty (LTS)             | PHP 5.5
`webdevops/php-apache:ubuntu-15.04`   | vivid                    | PHP 5.6
`webdevops/php-apache:ubuntu-15.10`   | wily                     | PHP 5.6
`webdevops/php-apache:debian-7`       | wheezy                   | PHP 5.4
`webdevops/php-apache:debian-8`       | jessie                   | PHP 5.6
`webdevops/php-apache:debian-8-php7`  | jessie with dotdeb       | PHP 7.x (via dotdeb)
`webdevops/php-apache:centos-7`       |                          | PHP 5.4


## Environment variables

Variable              | Description
--------------------- |  ------------------------------------------------------------------------------
`CLI_SCRIPT`          | Predefined CLI script for service
`APPLICATION_UID`     | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`     | PHP-FPM GID (Effective group ID)
`WEB_DOCUMENT_ROOT`   | Document root for Nginx
`WEB_DOCUMENT_INDEX`  | Document index (eg. `index.php`) for Nginx
`WEB_ALIAS_DOMAIN`    | Alias domains (eg. `*.vm`) for Nginx


## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/php-apache:latest         | [![](https://badge.imagelayers.io/webdevops/php-apache:latest.svg)](https://imagelayers.io/?images=webdevops/php-apache:latest 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-14.04   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-15.04   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/php-apache:ubuntu-15.10   | [![](https://badge.imagelayers.io/webdevops/php-apache:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/php-apache:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/php-apache:centos-7       | [![](https://badge.imagelayers.io/webdevops/php-apache:centos-7.svg)](https://imagelayers.io/?images=webdevops/php-apache:centos-7 'Get your own badge on imagelayers.io')
webdevops/php-apache:debian-8-php7  | [![](https://badge.imagelayers.io/webdevops/php-apache:debian-8-php-apache7.svg)](https://imagelayers.io/?images=webdevops/php-apache:debian-8-php-apache7 'Get your own badge on imagelayers.io')
webdevops/php-apache:debian-8       | [![](https://badge.imagelayers.io/webdevops/php-apache:debian-8.svg)](https://imagelayers.io/?images=webdevops/php-apache:debian-8 'Get your own badge on imagelayers.io')
webdevops/php-apache:debian-7       | [![](https://badge.imagelayers.io/webdevops/php-apache:debian-7.svg)](https://imagelayers.io/?images=webdevops/php-apache:debian-7 'Get your own badge on imagelayers.io')
