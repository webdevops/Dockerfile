#!/usr/bin/env bash

# Remove /usr/sbin/service (images have custom service script)
rm -rf /usr/sbin/service

# Link supervisor configuration script
ln -sf /opt/docker/etc/supervisor.conf /etc/supervisord.conf
