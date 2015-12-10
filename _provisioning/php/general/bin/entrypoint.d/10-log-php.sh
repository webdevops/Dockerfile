#!/usr/bin/env bash

createNamedPipe /var/log/php5-fpm.log
createNamedPipe /var/log/php.access.log
createNamedPipe /var/log/php.slow.log
createNamedPipe /var/log/php.error.log