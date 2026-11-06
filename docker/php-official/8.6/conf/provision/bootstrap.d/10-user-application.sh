#!/usr/bin/env bash

# Add group
groupadd -g "$APPLICATION_GID" "$APPLICATION_GROUP"

# Add user
useradd -u "$APPLICATION_UID" --home "/home/application" --create-home --shell /bin/bash --no-user-group "$APPLICATION_USER"

# Assign user to group
usermod -g "$APPLICATION_GROUP" "$APPLICATION_USER"
