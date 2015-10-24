#!/usr/bin/env bash

sed -i "/user = /c\user = ${APPLICATION_USER}"    /etc/php5/fpm/pool.d/www.conf
sed -i "/group = /c\group = ${APPLICATION_GROUP}" /etc/php5/fpm/pool.d/www.conf