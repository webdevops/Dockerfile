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
    # link stdout from docker
    if [[ -n "$LOG_STDOUT" ]]; then
        echo "Log stdout redirected to $LOG_STDOUT"
    else
        LOG_STDOUT="/proc/$$/fd/1"
    fi

    if [[ -n "$LOG_STDERR" ]]; then
        echo "Log stderr redirected to $LOG_STDERR"
    else
        LOG_STDERR="/proc/$$/fd/2"
    fi

    ln -f -s "$LOG_STDOUT" /docker.stdout
    ln -f -s "$LOG_STDERR" /docker.stderr
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
 #
 ##
function runEntrypoints() {
    # try to find entrypoint task script
    ENTRYPOINT_SCRIPT="/opt/docker/bin/entrypoint.d/${TASK}.sh"
    if [ ! -f "$ENTRYPOINT_SCRIPT" ]; then
        # use default
        ENTRYPOINT_SCRIPT="/opt/docker/bin/entrypoint.d/default.sh"
    fi

    if [ ! -f "$ENTRYPOINT_SCRIPT" ]; then
        exit 1
    fi

    . "$ENTRYPOINT_SCRIPT"
}

###
 # Run "entrypoint" provisioning
 #
 ##
function runProvisionEntrypoint() {
    includeScriptDir "/opt/docker/provision/entrypoint.d"
    includeScriptDir "/entrypoint.d"
}

###
 # https://stackoverflow.com/questions/41451159/how-to-execute-a-script-when-i-terminate-a-docker-container
 # https://hynek.me/articles/docker-signals/
 #
 ##
function runTeardownEntrypoint() {
    echo "Container stopped, performing teardown..."
    includeScriptDir "/opt/docker/provision/entrypoint.d/teardown"
    includeScriptDir "/entrypoint.d/teardown"
}

###
 # List environment variables (based on prefix)
 #
 ##
function envListVars() {
    if [[ $# -eq 1 ]]; then
        env | grep "^${1}" | cut -d= -f1
    else
        env | cut -d= -f1
    fi
}

###
 # Get environment variable (even with dots in name)
 #
 ##
function envGetValue() {
    awk "BEGIN {print ENVIRON[\"$1\"]}"
}
