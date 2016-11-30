#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

trap 'echo sigterm ; exit' SIGTERM
trap 'echo sigkill ; exit' SIGKILL

# link stdout from docker
ln -f -s "/proc/$$/fd/0" /docker.stdout
chmod 600 /docker.stdout

# sanitize input and set task
TASK="$(echo $1| sed 's/[^-_a-zA-Z0-9]*//g')"

source /opt/docker/bin/config.sh

if [[ "$UID" -eq 0 ]]; then
    # Only run provision if user is root

    if [ "$TASK" == "supervisord" -o "$TASK" == "noop" ]; then
        # Visible provisioning
        runProvisionEntrypoint
    else
        # Hidden provisioning
        runProvisionEntrypoint  > /dev/null
    fi
fi

#############################
## COMMAND
#############################

runEntrypoints "$@"
