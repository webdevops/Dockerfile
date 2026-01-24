#!/usr/bin/env bash

# Add group
addgroup -g "$APPLICATION_GID" "$APPLICATION_GROUP"

# Add user
adduser -D -u "$APPLICATION_UID" -h "/home/application" -s /bin/bash -G $APPLICATION_GROUP "$APPLICATION_USER"

