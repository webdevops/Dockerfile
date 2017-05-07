#!/usr/bin/env bash

# Add group
groupadd -g "$FTP_GID" "$FTP_USER"

# Add user
useradd -u "$FTP_UID" --create-home --shell /bin/bash --no-user-group "$FTP_USER"

# Assign user to group
usermod -g "$FTP_USER" "$FTP_USER"

# Set passwords to "dev"
echo "$FTP_USER":"$FTP_PASSWORD" | chpasswd
