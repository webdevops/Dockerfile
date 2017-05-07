#!/usr/bin/env bash

PHP_VERSION=$(php -r 'echo phpversion();')
IMAGE_FAMILY=$(docker-image-info family)
