#!/usr/bin/env bash

createNamedPipe /var/log/php-fpm/error.log
createNamedPipe /var/log/php.access.log
createNamedPipe /var/log/php.slow.log
createNamedPipe /var/log/php.error.logb