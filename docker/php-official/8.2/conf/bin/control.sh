#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

rootCheck "$0"

CONTROL_COMMAND="$1"
shift

case "$CONTROL_COMMAND" in

    ## ------------------------------------------
    ## PROVISION
    ## ------------------------------------------

    ## main roles
    "provision.role")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild --tag entrypoint "$1"
        ;;

    "provision.role.bootstrap")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap "$1"
        ;;

    "provision.role.build")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag build [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag build "$1"
        ;;

    "provision.role.onbuild")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag onbuild [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag onbuild "$1"
        ;;

    "provision.role.entrypoint")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag entrypoint [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag entrypoint "$1"
        ;;

    ## startup roles
    "provision.role.startup")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild --priority 50 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild --tag entrypoint  --priority 50 "$1"
        ;;

    "provision.role.startup.bootstrap")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap --priority 50 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap --priority 50 "$1"
        ;;

    "provision.role.startup.build")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag build --priority 50 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag build --priority 50 "$1"
        ;;

    "provision.role.startup.onbuild")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag onbuild --priority 50 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag onbuild --priority 50 "$1"
        ;;

    "provision.role.startup.entrypoint")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag entrypoint --priority 50 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag entrypoint --priority 50 "$1"
        ;;

    ## finish roles
    "provision.role.finish")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild --priority 200 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap --tag build --tag onbuild --tag entrypoint  --priority 200 "$1"
        ;;

    "provision.role.finish.bootstrap")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag bootstrap --priority 200 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag bootstrap --priority 200 "$1"
        ;;

    "provision.role.finish.build")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag build --priority 200 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag build --priority 200 "$1"
        ;;

    "provision.role.finish.onbuild")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag onbuild --priority 200 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag onbuild --priority 200 "$1"
        ;;

    "provision.role.finish.entrypoint")
        deprecationNotice " Please use >>/opt/docker/bin/provision add --tag entrypoint --priority 200 [role]<< for adding provision roles"
        /opt/docker/bin/provision add --tag entrypoint --priority 200 "$1"
        ;;

    ## ------------------------------------------
    ## Service
    ## ------------------------------------------

    "service.enable")
        deprecationNotice " Please use >>docker-service-enable [service]<<"
        docker-service-enable "$1"
        ;;

    "service.disable")
        deprecationNotice " Please use >>docker-service-disable [service]<<"
        docker-service-disable "$1"
        ;;

    ## ------------------------------------------
    ## Version
    ## ------------------------------------------

    "version.get")
        cat /opt/docker/VERSION
        ;;

    "version.require.min")
        EXPECTED_VERSION="$1"
        CURRENT_VERSION="$(cat /opt/docker/VERSION)"
        if [ "$CURRENT_VERSION" -lt "$EXPECTED_VERSION" ]; then
            echo "-----------------------------------------------------------"
            echo "--- This docker image is not up2date!"
            echo "--- "
            echo "--- Version expected min: $EXPECTED_VERSION"
            echo "--- Version current: $CURRENT_VERSION"
            echo "--- "
            echo "--- Run 'docker pull <imagename>' to update image"
            echo "-----------------------------------------------------------"
            exit 1
        fi
        ;;

    "version.require.max")
        EXPECTED_VERSION="$1"
        CURRENT_VERSION="$(cat /opt/docker/VERSION)"
        if [ "$CURRENT_VERSION"  -gt "$EXPECTED_VERSION" ]; then
            echo "-----------------------------------------------------------"
            echo "--- This docker image is too new!"
            echo "--- "
            echo "--- Version expected max: $EXPECTED_VERSION"
            echo "--- Version current: $CURRENT_VERSION"
            echo "-----------------------------------------------------------"
            exit 1
        fi
        ;;


    "buildtime.get")
        cat /opt/docker/BUILDTIME
        ;;

    *)
        echo "[ERROR] Invalid controll command: \"${CONTROL_COMMAND}\""
        exit 1
        ;;
esac
