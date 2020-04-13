#!/usr/bin/env bash

# Configure opcache for development
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*opcache.memory_consumption[\s]*='      -r 'opcache.memory_consumption = 256' \
    -s '^[\s;]*opcache.validate_timestamps[\s]*='     -r 'opcache.validate_timestamps = 1' \
    -s '^[\s;]*opcache.revalidate_freq[\s]*='         -r 'opcache.revalidate_freq = 0' \
    -s '^[\s;]*opcache.interned_strings_buffer[\s]*=' -r 'opcache.interned_strings_buffer = 16' \
    -s '^[\s;]*opcache.max_accelerated_files[\s]*='   -r 'opcache.max_accelerated_files = 7963' \
    -s '^[\s;]*opcache.fast_shutdown[\s]*='           -r 'opcache.fast_shutdown = 1' \
    -- /opt/docker/etc/php/php.webdevops.ini
