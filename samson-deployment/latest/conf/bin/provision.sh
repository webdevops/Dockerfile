#!/usr/bin/env bash

#
# Example:
# provision.sh /opt/foobar/playbook.yml tag
#
#

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

export PYTHONUNBUFFERED=1


if [ "$#" -lt 2 ]; then
    echo "[ERROR] $0: Playbook or tag is missing"
    exit 1
fi

ANSIBLE_PLAYBOOK="$1"
shift
ANSIBLE_TAG="$1"
shift
ANSIBLE_OPTS="$@"

# run ansible
exec ansible-playbook "${ANSIBLE_PLAYBOOK}" -i 'localhost,' --connection=local --tags="${ANSIBLE_TAG}" $ANSIBLE_OPTS
