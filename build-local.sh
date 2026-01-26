#!/usr/bin/env bash

set -exuo pipefail

ROOT_DIR=$(pwd)
PHP_VERSION=8.5

#docker run --rm -ti -v $PWD:/app -w /app webdevops/dockerfile-build-env make provision

# if first parameter is present overwrite php version + make sanity check

if [ "$#" -gt 0 ]; then
    PHP_VERSION=$1
    if [ ! -d "docker/php/$PHP_VERSION" ]; then
        echo "PHP version $PHP_VERSION not found"
        exit 1
    fi
fi

# alpine
docker build -t webdevops/php:$PHP_VERSION-alpine docker/php/$PHP_VERSION-alpine
docker build -t webdevops/php-dev:$PHP_VERSION-alpine docker/php-dev/$PHP_VERSION-alpine

docker build -t webdevops/php-nginx:$PHP_VERSION-alpine docker/php-nginx/$PHP_VERSION-alpine
docker build -t webdevops/php-nginx-dev:$PHP_VERSION-alpine docker/php-nginx-dev/$PHP_VERSION-alpine

docker build -t webdevops/php-apache:$PHP_VERSION-alpine docker/php-apache/$PHP_VERSION-alpine
docker build -t webdevops/php-apache-dev:$PHP_VERSION-alpine docker/php-apache-dev/$PHP_VERSION-alpine

# debian
docker build -t webdevops/php:$PHP_VERSION docker/php/$PHP_VERSION
docker build -t webdevops/php-dev:$PHP_VERSION docker/php-dev/$PHP_VERSION

docker build -t webdevops/php-nginx:$PHP_VERSION docker/php-nginx/$PHP_VERSION
docker build -t webdevops/php-nginx-dev:$PHP_VERSION docker/php-nginx-dev/$PHP_VERSION

docker build -t webdevops/php-apache:$PHP_VERSION docker/php-apache/$PHP_VERSION
docker build -t webdevops/php-apache-dev:$PHP_VERSION docker/php-apache-dev/$PHP_VERSION

docker images | grep webdevops | grep $PHP_VERSION
