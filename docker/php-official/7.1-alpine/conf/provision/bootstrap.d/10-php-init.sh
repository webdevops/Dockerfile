#!/usr/bin/env bash

PHP_VERSION=$(php -r 'echo phpversion();' | cut -d '-' -f 1)
IMAGE_FAMILY=$(docker-image-info family)
