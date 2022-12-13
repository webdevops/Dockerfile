#!/usr/bin/env bash

# Create /app folder
mkdir -p /app
chown "$APPLICATION_USER":"$APPLICATION_GROUP" /app
