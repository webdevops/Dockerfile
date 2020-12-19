#!/usr/bin/env bash

if [[ -z "$CONTAINER_UID" ]]; then
    export CONTAINER_UID=1000
fi

set -o pipefail # trace ERR through pipes
set -o errtrace # trace ERR through 'time command' and other functions
set -o nounset  ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit  ## set -e : exit the script if any statement returns a non-true return value

# auto elevate privileges (if container is not started as root)
if [[ "$UID" -ne 0 ]]; then
    export CONTAINER_UID="$UID"
    exec gosu root "$0" "$@"
fi

. /opt/docker/bin/config.sh
createDockerStdoutStderr

# sanitize input and set task
TASK="$(echo $1 | sed 's/[^-_a-zA-Z0-9]*//g')"

if [ "$TASK" == "supervisord" ] || [ "$TASK" == "noop" ]; then
    # visible provisioning
    runProvisionEntrypoint
    trap 'runTeardownEntrypoint' SIGTERM
    runEntrypoints "$@" &
    wait $!
    runTeardownEntrypoint
else
    # hidden provisioning
    runProvisionEntrypoint > /dev/null
    runEntrypoints "$@"
fi
