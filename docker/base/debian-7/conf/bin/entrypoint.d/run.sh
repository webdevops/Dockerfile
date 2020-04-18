#!/usr/bin/env bash

if [[ -z "$CONTAINER_UID" ]]; then
    export CONTAINER_UID=application
fi

set -o pipefail # trace ERR through pipes
set -o errtrace # trace ERR through 'time command' and other functions
set -o nounset  ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit  ## set -e : exit the script if any statement returns a non-true return value

. /opt/docker/bin/config.sh

# auto elevate privileges (if container is not started as root)
if [[ "$UID" -ne 0 ]]; then
    export CONTAINER_UID="$UID"
    exec gosu root "$0" "$@"
fi

createDockerStdoutStderr

# sanitize input and set task
TASK="$(echo $1 | sed 's/[^-_a-zA-Z0-9]*//g')"

# remove suid bit `chmod -s /sbin/gosu` in provision/entrypoint.d/05-gosu.sh
if [ "$TASK" == "supervisord" ] || [ "$TASK" == "noop" ]; then
    # visible provisioning
    runProvisionEntrypoint
else
    # hidden provisioning
    runProvisionEntrypoint >/dev/null
fi

# https://stackoverflow.com/questions/41451159/how-to-execute-a-script-when-i-terminate-a-docker-container
# https://hynek.me/articles/docker-signals/
function teardownEntrypoint()
{
    # restore suid bit `chmod +s /sbin/gosu` in provision/entrypoint.d/teardown/05-gosu.sh
    echo "Container stopped, performing teardown..."
    includeScriptDir /opt/docker/provision/entrypoint.d/teardown
}
trap 'teardownEntrypoint' SIGTERM
runEntrypoints &
wait $!
teardownEntrypoint
