#!/usr/bin/env bash

shopt -s nullglob

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
    for FILE in /opt/docker/bin/bootstrap.d/*.sh; do
        . "$FILE"
        rm -f -- "$FILE"
    done

    runDockerProvision bootstrap

    for TASKFILE in /opt/docker/provision/roles/*/tasks/bootstrap/*.yml; do
        echo "--- " > "$TASKFILE"
    done
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

    bash /opt/docker/bin/provision.sh "${ANSIBLE_PLAYBOOK}" "${ANSIBLE_TAG}"
}

function startSupervisord() {
    cd /
    exec supervisord -c /opt/docker/etc/supervisor.conf --logfile /dev/null --pidfile /dev/null --user root
}