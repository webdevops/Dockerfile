#!/usr/bin/env bash

shopt -s nullglob

IMAGE_FAMILY=$(cat /etc/dockerimage_distribution_family)
IMAGE_DISTRIBUTION=$(cat /etc/dockerimage_distribution)
IMAGE_DISTRIBUTION_VERSION=$(cat /etc/dockerimage_distribution_version)
IMAGE_DISTRIBUTION_CODENAME=$(cat /etc/dockerimage_lsb_codename)

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
    if [[ -z "$LOG_STDOUT" ]]; then
        LOG_STDOUT="/proc/$$/fd/1"
    fi

    if [[ -z "$LOG_STDERR" ]]; then
        LOG_STDERR="/proc/$$/fd/2"
    fi

    ln -f -s "$LOG_STDOUT" /docker.stdout
    ln -f -s "$LOG_STDERR" /docker.stderr
    chmod 600 /docker.stdout /docker.stderr
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
        # run custom scripts, only once
        . "$FILE"
        rm -f -- "$FILE"
    done

    runDockerProvision bootstrap
}

###
 # Run "build" provisioning
 ##
function runProvisionBuild() {
    for FILE in /opt/docker/provision/build.d/*.sh; do
        # run custom scripts, only once
        . "$FILE"
    done

    runDockerProvision build
}

###
 # Run "onbuild" provisioning
 ##
function runProvisionOnBuild() {
    includeScriptDir "/opt/docker/provision/onbuild.d"

    runDockerProvision onbuild
}

###
 # Run "entrypoint" provisioning
 ##
function runProvisionEntrypoint() {
    includeScriptDir "/opt/docker/provision/entrypoint.d"
    includeScriptDir "/entrypoint.d"

    runDockerProvision entrypoint
}


###
 # Run docker provisioning with dyniamic playbook generation
 #
 # $1 -> playbook tag (bootstrap, onbuild, entrypoint)
 #
 ##
function runDockerProvision() {
    ANSIBLE_TAG="$1"

    PROVISION_STATS_FILE="/opt/docker/etc/.registry/provision-stats.${ANSIBLE_TAG}"

    # run provision if stats file doesn't exists (unknown mode)
    # or if stats file is not empty
    if [[ ! -f "$PROVISION_STATS_FILE" ]] || [[ -s "$PROVISION_STATS_FILE" ]]; then
        /opt/docker/bin/provision run --tag "${ANSIBLE_TAG}" --use-registry
    fi
}

