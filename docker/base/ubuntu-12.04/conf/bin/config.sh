#!/usr/bin/env bash

shopt -s nullglob

PROVISION_REGISTRY_PATH="/opt/docker/etc/.registry"
PROVISION_REGISTRY_PATH="/opt/docker/etc/.registry"

###
 # Check if current user is root
 #
 ##
function rootCheck() {
    # Root check
    if [ "$(/usr/bin/whoami)" != "root" ]; then
        echo "[ERROR] Must be run as root"
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

    ## Reset bootstrap provision list (prevent re-run)
    rm -f ${PROVISION_REGISTRY_PATH}/provision.*.bootstrap
}

###
 # Run "build" provisioning
 ##
function runProvisionBuild() {
    for FILE in /opt/docker/provision/build.d/*.sh; do
        # run custom scripts, only once
        . "$FILE"
        rm -f -- "$FILE"
    done

    runDockerProvision build
}

###
 # Run "onbuild" provisioning
 ##
function runProvisionOnBuild() {
    for FILE in /opt/docker/provision/onbuild.d/*.sh; do
        # run custom scripts
        . "$FILE"
    done

    runDockerProvision onbuild
}

###
 # Run "entrypoint" provisioning
 ##
function runProvisionEntrypoint() {
    for FILE in /opt/docker/provision/entrypoint.d/*.sh; do
        # run custom scripts
        . "$FILE"
    done

    runDockerProvision entrypoint
}

###
 # Add role to provision registry
 #
 # $1 -> registry type (bootstrap, onbuild, entrypoint...)
 # $2 -> role
 #
 ##
function provisionRoleAdd() {
    PROVISION_FILE="${PROVISION_REGISTRY_PATH}/$1"
    PROVISION_ROLE="$2"

    mkdir -p -- "${PROVISION_REGISTRY_PATH}"
    touch -- "${PROVISION_FILE}"

    echo "${PROVISION_ROLE}" >> "${PROVISION_FILE}"
}

###
 # Build list of roles for this registry provision type (playbook building)
 #
 # $1 -> registry type (bootstrap, onbuild, entrypoint...)
 #
 ##
function buildProvisionRoleList() {
    PROVISION_FILE="${PROVISION_REGISTRY_PATH}/$1"

    if [ -s "${PROVISION_FILE}" ]; then
        # Add registered roles
        for ROLE in $(cat "$PROVISION_FILE"); do
            echo "    - { role: \"$ROLE\" }"
        done
    fi
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

