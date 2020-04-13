#!/usr/bin/env bash

# Disable blackfire by default
rm -f \
    /etc/php5/cli/conf.d/zz-blackfire.ini \
    /etc/php5/fpm/conf.d/zz-blackfire.ini
