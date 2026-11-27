# Image tests: marks that a service sourced the hooks in its directory, and as which user, see ServiceChecks
mkdir -p /tmp/image-tests/hooks
id -un > "/tmp/image-tests/hooks/$(basename "$(dirname "${BASH_SOURCE[0]}")")"
# and writes a line to the stdout and one to the stderr of the service, see ContainerEnvironmentChecks
echo "image-test: the hook of syslog-ng on stdout"
echo "image-test: the hook of syslog-ng on stderr" >&2
