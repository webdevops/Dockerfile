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

. config.sh

deprecationNotice " Please use >>/opt/docker/bin/provision run --playbook playbook.yml --tag=bootstrap [args]<< for running provision"


if [ "$#" -lt 2 ]; then
    echo "[ERROR] $0: Playbook or tag is missing"
    exit 1
fi

ANSIBLE_PLAYBOOK="$1"
shift
ANSIBLE_TAG="$1"
shift
ANSIBLE_OPTS="$@"

/opt/docker/bin/provision run --playbook "${ANSIBLE_PLAYBOOK}" --use-registry --tag "${ANSIBLE_TAG}" $ANSIBLE_OPTS
