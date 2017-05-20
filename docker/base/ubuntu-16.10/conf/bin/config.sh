#!/usr/bin/env bash

shopt -s nullglob

###
 # Check if current user is root
 #
 ##
function rootCheck() {
    # Root check
    if [ "$(/usr/bin/whoami)" != "root" ]; then
        echo "[ERROR] $* must be run as root"
        exit 1
    fi
}

###
 # Create /docker.stdout and /docker.stderr
 #
 ##
function createDockerStdoutStderr() {
    mkfifo /docker.stdout
    mkfifo /docker.stderr

    chmod 666 /docker.stdout /docker.stderr
}

###
 # Include script directory text inside a file
 #
 # $1 -> path
 #
 ##
function includeScriptDir() {
    if [[ -d "$1" ]]; then
        for FILE in "$1"/*.sh; do
            echo "-> Executing ${FILE}"
            # run custom scripts, only once
            . "$FILE"
        done
    fi
}

###
 # Show deprecation notice
 #
 ##
function deprecationNotice() {
    echo ""
    echo "###############################################################################"
    echo "###      THIS CALL IS DEPRECATED AND WILL BE REMOVED IN THE FUTURE"
    echo "###"
    echo "### $*"
    echo "###"
    echo "###############################################################################"
    echo ""
}

###
 # Run "entrypoint" scripts
 ##
function runEntrypoints() {
    ###############
    # Try to find entrypoint
    ###############

    ENTRYPOINT_SCRIPT="/opt/docker/bin/entrypoint.d/${TASK}.sh"

    if [ -f "$ENTRYPOINT_SCRIPT" ]; then
        . "$ENTRYPOINT_SCRIPT"
    fi

    ###############
    # Run default
    ###############
    if [ -f "/opt/docker/bin/entrypoint.d/default.sh" ]; then
        . /opt/docker/bin/entrypoint.d/default.sh
    fi

    exit 1
}

###
 # Run "bootstrap" provisioning
 ##
function runProvisionBootstrap() {
    for FILE in /opt/docker/provision/bootstrap.d/*.sh; do
        echo "-> Executing ${FILE}"

        # run custom scripts, only once
        . "$FILE"
        rm -f -- "$FILE"
    done
}

###
 # Run "build" provisioning
 ##
function runProvisionBuild() {
    includeScriptDir "/opt/docker/provision/build.d"
}

###
 # Run "onbuild" provisioning
 ##
function runProvisionOnBuild() {
    includeScriptDir "/opt/docker/provision/onbuild.d"
}

###
 # Run "entrypoint" provisioning
 ##
function runProvisionEntrypoint() {
    includeScriptDir "/opt/docker/provision/entrypoint.d"
    includeScriptDir "/entrypoint.d"
}
