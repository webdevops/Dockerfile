#!/usr/bin/env bash

shopt -s nullglob

function createNamedPipe() {
    rm --force -- "$1"
    mknod "$1" p
}

function initBootstrap() {
    for FILE in /opt/docker/bin/bootstrap.d/*.sh; do
        . "$FILE"
    done
}

function initEntrypoint() {
    for FILE in /opt/docker/bin/entrypoint.d/*.sh; do
        . "$FILE"
    done
}

function startSupervisord() {
    cd /
    exec supervisord -c /opt/docker/etc/supervisord.conf --logfile /dev/null --pidfile /dev/null --user root
}