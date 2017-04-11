#!/usr/bin/env bash

BOOTSTRAP_MODE="bootstrap"

if [ -n "$1" ]; then
    BOOTSTRAP_MODE="$1"
fi

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

rootCheck "$0"

# Save the buildtime
date +%s > /opt/docker/BUILDTIME

# Make all scripts executable
find /opt/docker/bin/ -type f -iname '*.sh' -print0 | xargs --no-run-if-empty -0 chmod +x

# Enable usr-bin executables
find /opt/docker/bin/usr-bin -type f | while read USR_BIN_FILE; do
    chmod +x -- "$USR_BIN_FILE"
    ln -n -f -- "$USR_BIN_FILE" "/usr/local/bin/$(basename "$USR_BIN_FILE")"
done

case "$BOOTSTRAP_MODE" in
    ###################################
    # When container will be build next time
    ###################################
    "onbuild")
        # Init and run bootstrap system
        runProvisionOnBuild
        ;;

    ###################################
    # When container is build this time
    ###################################
    "bootstrap")
        # Init and run bootstrap system
        runProvisionBootstrap
        runProvisionBuild
        ;;

    *)
        echo "[ERROR] Bootstrap mode '$BOOTSTRAP_MODE' not defined"
        exit 1
        ;;
esac


######################
# Check if entrypoint provisioning is available
######################

echo "Checking provision status..."
for PROVISION_TAG in onbuild build bootstrap entrypoint; do
    ROLES=$(docker-provision list --tag "$PROVISION_TAG")
    if [[ -n "$ROLES" ]]; then
        PROVISION_ROLE_COUNT=$(echo "$ROLES" | wc -l)
        echo "  - found ${PROVISION_ROLE_COUNT} for provision tag ${PROVISION_TAG}, caching info for execution"
        echo -n "$PROVISION_ROLE_COUNT" > "/opt/docker/etc/.registry/provision-availale.${PROVISION_TAG}"
    else
        echo "  - no roles found for provision tag ${PROVISION_TAG}"
        echo -n '' > "/opt/docker/etc/.registry/provision-availale.${PROVISION_TAG}"
    fi
done

