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

# Check if DOCKER_IMAGE is set, needed for test
if [[ -z "$DOCKER_IMAGE" ]]; then
    echo "Environment variable 'DOCKER_IMAGE' not set"
    exit 1
fi

# Check if DOCKER_TAG is set, needed for test
if [[ -z "$DOCKER_TAG" ]]; then
    echo "Environment variable 'DOCKER_TAG' not set"
    exit 1
fi

# Check if OS_FAMILY is set, needed for test
if [[ -z "$OS_FAMILY" ]]; then
    echo "Environment variable 'OS_FAMILY' not set"
    exit 1
fi

# Check if OS_FAMILY is set, needed for test
if [[ -z "$OS_VERSION" ]]; then
    echo "Environment variable 'OS_FAMILY' not set"
    exit 1
fi

echo "Starting serverspec"
echo "            OS: ${OS_FAMILY} Version ${OS_VERSION}"
echo "  Docker image: ${DOCKER_IMAGE}:${DOCKER_TAG}"
echo "    Dockerfile: ${DOCKERFILE}"
echo ""

exec bundle exec rake spec["$1","$DOCKERFILE","$OS_FAMILY","$OS_VERSION","$DOCKER_IMAGE","$DOCKER_TAG"] "$@"
