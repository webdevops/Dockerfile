#!/usr/bin/env bash

GOCROND_VERSION=0.4.0
wget -O /usr/local/bin/go-crond https://github.com/webdevops/go-crond/releases/download/$GOCROND_VERSION/go-crond-64-linux
chmod +x /usr/local/bin/go-crond
