#!/usr/bin/env bash

shopt -s nullglob

PROVISION_REGISTRY_PATH="/opt/docker/bin/.registry"


function createNamedPipe() {
    rm --force -- "$1"
    mknod "$1" p
}

function sedEscape() {
    echo "$(echo $* |sed -e 's/[]\/$*.^|[]/\\&/g')"
}

function replaceTextInFile() {
    SOURCE="$(sedEscape $1)"
    REPLACE="$(sedEscape $2)"
    TARGET="$3"

    sed -i "s/${SOURCE}/${REPLACE}/" "${TARGET}"
}

function initBootstrap() {
    mkdir -p /opt/docker/bin/registry/

    for FILE in /opt/docker/bin/bootstrap.d/*.sh; do
        . "$FILE"
        rm -f -- "$FILE"
    done

    runDockerProvision bootstrap

    ## Reset bootstrap provision list (prevent re-run)b
    rm -f "${PROVISION_REGISTRY_PATH}/provision.bootstrap"
}

function initEntrypoint() {
    for FILE in /opt/docker/bin/entrypoint.d/*.sh; do
        . "$FILE"
    done

    runDockerProvision entrypoint
}

function runDockerProvision() {
    ANSIBLE_PLAYBOOK="/opt/docker/provision/playbook.yml"
    ANSIBLE_TAG="$1"

    ANSIBLE_PROVISION_REGISTRY="${PROVISION_REGISTRY_PATH}/provision.${ANSIBLE_TAG}"
    ANSIBLE_DYNAMIC_PLAYBOOK=0

    ## Create dynamic ansible playbook file
    if [ ! -f "$ANSIBLE_PLAYBOOK" -a -s "$ANSIBLE_PROVISION_REGISTRY" ]; then
        ## Create dynamic playbook file
        echo "---

- hosts: all
  vars_files:
    - "./variables.yml"
  roles:
" > "$ANSIBLE_PLAYBOOK"

        # Add registered roles
        for ROLE in $(cat $ANSIBLE_PROVISION_REGISTRY); do
            echo "    - { role: \"$ROLE\" }" >> "$ANSIBLE_PLAYBOOK"
        done

        ANSIBLE_DYNAMIC_PLAYBOOK=1
    fi

    # Only run playbook if there is one
    if [ -f "${ANSIBLE_PLAYBOOK}" ]; then
        bash /opt/docker/bin/provision.sh "${ANSIBLE_PLAYBOOK}" "${ANSIBLE_TAG}"

        # Remove dynamic playbook file
        if [ "${ANSIBLE_DYNAMIC_PLAYBOOK}" -eq 1 ]; then
            rm -f "${ANSIBLE_PLAYBOOK}"
        fi
    fi
}

function startSupervisord() {
    cd /
    exec supervisord -c /opt/docker/etc/supervisor.conf --logfile /dev/null --pidfile /dev/null --user root
}
