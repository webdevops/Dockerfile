#!/bin/bash

#
# Test bootstrap images
#
echo "Testing bootstrap images"
# Test existence of baselayout files
container-structure-test test --image webdevops/bootstrap:alpine --config bootstrap/files.yaml
# Test docker-image-info commands
container-structure-test test --image webdevops/bootstrap:alpine --config bootstrap/alpine/commands.yaml
