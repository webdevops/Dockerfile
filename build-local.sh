#!/usr/bin/env bash

set -exuo pipefail

ROOT_DIR=$(pwd)
PHP_VERSION=8.4

#docker run --rm -ti -v $PWD:/app -w /app webdevops/dockerfile-build-env make provision

# if first parameter is present overwrite php version + make sanity check

if [ "$#" -gt 0 ]; then
    PHP_VERSION=$1
    if [ ! -d "$ROOT_DIR/docker/php/$PHP_VERSION" ]; then
        echo "PHP version $PHP_VERSION not found"
        exit 1
    fi
fi

# alpine
cd $ROOT_DIR/docker/php/$PHP_VERSION-alpine
docker build -t webdevops/php:$PHP_VERSION-alpine .
cd $ROOT_DIR/docker/php-dev/$PHP_VERSION-alpine
docker build -t webdevops/php-dev:$PHP_VERSION-alpine .

cd $ROOT_DIR/docker/php-nginx/$PHP_VERSION-alpine
docker build -t webdevops/php-nginx:$PHP_VERSION-alpine .
cd $ROOT_DIR/docker/php-nginx-dev/$PHP_VERSION-alpine
docker build -t webdevops/php-nginx-dev:$PHP_VERSION-alpine .

cd $ROOT_DIR/docker/php-apache/$PHP_VERSION-alpine
docker build -t webdevops/php-apache:$PHP_VERSION-alpine .
cd $ROOT_DIR/docker/php-apache-dev/$PHP_VERSION-alpine
docker build -t webdevops/php-apache-dev:$PHP_VERSION-alpine .

# debian
cd $ROOT_DIR/docker/php/$PHP_VERSION
docker build -t webdevops/php:$PHP_VERSION .
cd $ROOT_DIR/docker/php-dev/$PHP_VERSION
docker build -t webdevops/php-dev:$PHP_VERSION .

cd $ROOT_DIR/docker/php-nginx/$PHP_VERSION
docker build -t webdevops/php-nginx:$PHP_VERSION .
cd $ROOT_DIR/docker/php-nginx-dev/$PHP_VERSION
docker build -t webdevops/php-nginx-dev:$PHP_VERSION .

cd $ROOT_DIR/docker/php-apache/$PHP_VERSION
docker build -t webdevops/php-apache:$PHP_VERSION .
cd $ROOT_DIR/docker/php-apache-dev/$PHP_VERSION
docker build -t webdevops/php-apache-dev:$PHP_VERSION .

docker images | grep webdevops | grep $PHP_VERSION
