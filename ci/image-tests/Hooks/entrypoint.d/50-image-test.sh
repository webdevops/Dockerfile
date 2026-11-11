# Image tests: marks that the entrypoint sourced the scripts in /entrypoint.d, and as which user, see EntrypointChecks
mkdir -p /tmp/image-tests
id -un > /tmp/image-tests/entrypoint.d
