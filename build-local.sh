#!/usr/bin/env bash

set -exuo pipefail

ROOT_DIR=$(pwd)
PHP_VERSION=8.5

# if first parameter is present overwrite php version + make sanity check

if [ "$#" -gt 0 ]; then
    PHP_VERSION=$1
    if [ ! -d "docker/php/$PHP_VERSION" ]; then
        echo "PHP version $PHP_VERSION not found"
        exit 1
    fi
fi

# PIE takes a GitHub token from COMPOSER_AUTH, see "PHP extension builds" in CONTRIBUTING.md
SECRET=""
if [[ -n "${COMPOSER_AUTH:-}" ]]; then
    SECRET="--secret id=COMPOSER_AUTH,env=COMPOSER_AUTH"
fi

# alpine
docker build $SECRET -t webdevops/php:$PHP_VERSION-alpine docker/php/$PHP_VERSION-alpine
docker build $SECRET -t webdevops/php-dev:$PHP_VERSION-alpine docker/php-dev/$PHP_VERSION-alpine

docker build $SECRET -t webdevops/php-nginx:$PHP_VERSION-alpine docker/php-nginx/$PHP_VERSION-alpine
docker build $SECRET -t webdevops/php-nginx-dev:$PHP_VERSION-alpine docker/php-nginx-dev/$PHP_VERSION-alpine

docker build $SECRET -t webdevops/php-apache:$PHP_VERSION-alpine docker/php-apache/$PHP_VERSION-alpine
docker build $SECRET -t webdevops/php-apache-dev:$PHP_VERSION-alpine docker/php-apache-dev/$PHP_VERSION-alpine

# debian
docker build $SECRET -t webdevops/php:$PHP_VERSION docker/php/$PHP_VERSION
docker build $SECRET -t webdevops/php-dev:$PHP_VERSION docker/php-dev/$PHP_VERSION

docker build $SECRET -t webdevops/php-nginx:$PHP_VERSION docker/php-nginx/$PHP_VERSION
docker build $SECRET -t webdevops/php-nginx-dev:$PHP_VERSION docker/php-nginx-dev/$PHP_VERSION

docker build $SECRET -t webdevops/php-apache:$PHP_VERSION docker/php-apache/$PHP_VERSION
docker build $SECRET -t webdevops/php-apache-dev:$PHP_VERSION docker/php-apache-dev/$PHP_VERSION

docker images | grep webdevops | grep $PHP_VERSION
