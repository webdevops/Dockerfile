#!/usr/bin/env bash

# Set passwords to "dev"
echo "$APPLICATION_USER":"dev" | chpasswd
echo "root":"dev" | chpasswd
