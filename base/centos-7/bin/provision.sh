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

ANSIBLE_PLAYBOOK="$1"
shift
ANSIBLE_TAG="$1"
shift
ANSIBLE_OPTS="$@"

# workaround if windows
chmod -x "$ANSIBLE_DIR/inventory"

# run ansible
ansible-playbook "${ANSIBLE_PLAYBOOK}" --connection=local --tags="${ANSIBLE_TAG}" $ANSIBLE_OPTS