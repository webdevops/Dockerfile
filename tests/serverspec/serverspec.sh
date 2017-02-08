#!/usr/bin/env bash


echo "Starting serverspec"
echo "            OS: ${OS_FAMILY} Version ${OS_VERSION}"
echo "  Docker image: ${DOCKER_IMAGE}:${DOCKER_TAG}"
echo "    Dockerfile: ${DOCKERFILE}"
echo ""

RAILS_ENV=development bundle exec rake spec["$1","$2"]
ret=$?

echo "Finished serverspec"
echo "            OS: ${OS_FAMILY} Version ${OS_VERSION}"
echo "  Docker image: ${DOCKER_IMAGE}:${DOCKER_TAG}"
echo "    Dockerfile: ${DOCKERFILE}"
echo ""

exit $ret
