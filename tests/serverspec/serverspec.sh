#!/usr/bin/env bash

# Check if DOCKERFILE is set, needed for test
if [[ -z "$DOCKERFILE" ]]; then
    echo "Environment variable 'DOCKERFILE' not set"
    exit 1
fi

# Check if dockerfile exists
if [[ ! -f "${DOCKERFILE}" ]]; then
    # Filesystem is maybe not synced?
    sync

    # recheck if file is now available
    if [[ ! -f "${DOCKERFILE}" ]]; then
        echo "Dockerfile $DOCKERFILE' not found"
        exit 1
    fi
fi

exec bundle exec rspec "$@"
