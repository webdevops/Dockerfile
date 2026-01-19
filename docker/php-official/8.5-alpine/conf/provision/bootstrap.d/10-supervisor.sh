#!/usr/bin/env bash

# Remove /usr/sbin/service (images have custom service script)
rm -rf /usr/sbin/service

# Remove existing supervisor configuration
rm -rf -- /etc/supervisor*

# Link supervisor configuration script
ln -sf /opt/docker/etc/supervisor.conf /etc/supervisord.conf
