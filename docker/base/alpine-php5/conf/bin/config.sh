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
 # Create named pipe
 #
 # $1 -> name of file
 #
 ##
function createNamedPipe() {
    rm --force -- "$1"
    mknod "$1" p
}

###
 # Escape value for sed usage
 #
 # $1     -> value
 # STDOUT -> escaped value
 #
 ##
function sedEscape() {
    echo "$(echo $* |sed -e 's/[]\/$*.^|[]/\\&/g')"
}

###
 # Replace text inside a file
 #
 # $1 -> source value
 # $2 -> target value
 # $3 -> path to file
 #
 ##
function replaceTextInFile() {
    SOURCE="$(sedEscape $1)"
    REPLACE="$(sedEscape $2)"
    TARGET="$3"

    sed -i "s/${SOURCE}/${REPLACE}/" "${TARGET}"
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

    exit
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

    /opt/docker/bin/provision run --tag "$ANSIBLE_TAG" --use-registry
}

