# Image tests: marks that a service sourced the hooks in its directory, and as which user, see ServiceChecks
mkdir -p /tmp/image-tests/hooks
id -un > "/tmp/image-tests/hooks/$(basename "$(dirname "${BASH_SOURCE[0]}")")"
