#!/usr/bin/env bash

# Link main entrypoint script to /entrypoint
ln -sf /opt/docker/bin/entrypoint.sh /entrypoint

# Link entrypoint cmd shortcut conf directory to /entrypoint.cmd
ln -sf /opt/docker/bin/entrypoint.d /entrypoint.cmd

# Create /entrypoint.d
mkdir -p /entrypoint.d
chmod 700 /entrypoint.d
chown root:root /entrypoint.d


