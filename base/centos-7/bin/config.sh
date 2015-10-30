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
    rm -f "${PROVISION_REGISTRY_PATH}/provision.*.bootstrap"
}

function initEntrypoint() {
    for FILE in /opt/docker/bin/entrypoint.d/*.sh; do
        . "$FILE"
    done

    runDockerProvision entrypoint
}

function provisionRoleAdd() {
    PROVISION_FILE="${PROVISION_REGISTRY_PATH}/$1"
    PROVISION_ROLE="$2"

    mkdir -p -- "${PROVISION_REGISTRY_PATH}"
    touch -- "${PROVISION_FILE}"

    echo "${PROVISION_ROLE}" >> "${PROVISION_FILE}"
}

function buildProvisionRoleList() {
    PROVISION_FILE="${PROVISION_REGISTRY_PATH}/$1"

    if [ -s "${PROVISION_FILE}" ]; then
        # Add registered roles
        for ROLE in $(cat "$PROVISION_FILE"); do
            echo "    - { role: \"$ROLE\" }"
        done
    fi
}

function runDockerProvision() {
    ANSIBLE_PLAYBOOK="/opt/docker/provision/playbook.yml"
    ANSIBLE_TAG="$1"
    ANSIBLE_DYNAMIC_PLAYBOOK=0

    ## Create dynamic ansible playbook file
    if [ ! -f "$ANSIBLE_PLAYBOOK" ]; then
        ## Create dynamic playbook file
        echo "---

- hosts: all
  vars_files:
    - "./variables.yml"
  roles:
" > "$ANSIBLE_PLAYBOOK"

        buildProvisionRoleList "provision.startup.${ANSIBLE_TAG}" >> "$ANSIBLE_PLAYBOOK"
        buildProvisionRoleList "provision.main.${ANSIBLE_TAG}"    >> "$ANSIBLE_PLAYBOOK"
        buildProvisionRoleList "provision.finish.${ANSIBLE_TAG}"  >> "$ANSIBLE_PLAYBOOK"

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
