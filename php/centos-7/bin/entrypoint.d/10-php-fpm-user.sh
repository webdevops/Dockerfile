#!/usr/bin/env bash

sed -i "/user = /c\user = ${APPLICATION_USER}"    /etc/php-fpm.d/www.conf
sed -i "/group = /c\group = ${APPLICATION_GROUP}" /etc/php-fpm.d/www.conf