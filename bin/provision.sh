#!/usr/bin/env bash

if [[ -n "$1" ]]; then
    BUILD_TARGET="$1"
else
    BUILD_TARGET="all"
fi

if [[ "$BUILD_MODE" == "push" ]]; then
    # skip provision on push
    exit 0
fi

if [[ "$BASELAYOUT" -eq 1 ]]; then
    BASELAYOUT=1
else
    BASELAYOUT=0
fi

if [[ "$PROVISION" -eq 1 ]]; then
    PROVISION=1
else
    PROVISION=0
fi

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

# Fix readlink issue on macos

READLINK='readlink'
TAR='tar'

[[ `uname` == 'Darwin' ]] && {
	which greadlink > /dev/null && {
		READLINK='greadlink'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils gnu-sed'
		exit 1
	}

	which gtar > /dev/null && {
		TAR='gtar'
	} || {
		echo 'ERROR: GNU tar required for Mac. You may use homebrew to install them: brew install gnu-tar'
		exit 1
	}
}

SCRIPT_DIR=$(dirname "$($READLINK -f "$0")")
BASE_DIR=$(dirname "$SCRIPT_DIR")

source "$SCRIPT_DIR/functions.sh"

BASELAYOUT_DIR="${BASE_DIR}/baselayout"
PROVISION_DIR="${BASE_DIR}/provisioning"
MACRO_DIR="${BASE_DIR}/macro"
DOCKER_DIR="${BASE_DIR}/docker"


###
 # Relative dir
 #
 # $1     -> absolute path
 # stdout -> relative path (to current base dir)
 #
 ##
function relativeDir() {
    echo ${1#${BASE_DIR}/}
}

###
 # Relative dir
 #
 # $1     -> build target (eg. "bootstrap", "base", "php" ...)
 # stdout -> "1" if target is matched
 #
 ##
function checkBuildTarget() {
    if [ "$BUILD_TARGET" == "all" -o "$BUILD_TARGET" == "$1" ]; then
        echo 1
    fi
}

###
 # Generate list of directories
 #
 # $1     -> Directory
 #
 ##
function listDirectories() {
    find "$1" -maxdepth 1 -type d
}

###
 # Generate list of directories with iname filter
 #
 # $1     -> Directory
 # s2     -> Filter (find iname)
 #
 ##
function listDirectoriesWithFilter() {
    find "$1" -maxdepth 1 -type d -iname "$2"
}

#######################################
# Localscripts
#######################################

###
 # Build localscripts
 #
 # Build tar file from _localscripts for bootstrap containers
 #
 ##
function buildBaselayout() {
    if [[ "$BASELAYOUT" -eq 1 ]]; then
        echo " * Building localscripts"

        cd "${BASELAYOUT_DIR}"
        rm -f baselayout.tar
        $TAR -jc --owner=0 --group=0 -f baselayout.tar *
    fi
}

###
 # Deploy localscripts
 #
 # Copy tar to various containers
 #
 ##
function deployBaselayout() {
    if [[ "$BASELAYOUT" -eq 1 ]]; then
        DOCKER_CONTAINER="$1"
        DOCKER_FILTER="$2"

        listDirectoriesWithFilter "${DOCKER_DIR}/${DOCKER_CONTAINER}" "${DOCKER_FILTER}"  | while read DOCKER_DIR; do
            if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
                echo "    - $(relativeDir $DOCKER_DIR)"
                cp baselayout.tar "${DOCKER_DIR}/baselayout.tar"
            fi
        done
    fi
}

#######################################
# Configuration
#######################################

###
 # Clear configuration
 #
 # Clear conf/ directory of each docker container
 #
 # $1 -> container name (eg. php)
 # $2 -> sub directory filter (eg. "*" for all or "ubuntu-*" for only ubuntu containers)
 #
 ##
function clearConfiguration() {
    if [[ "$PROVISION" -eq 1 ]]; then
        DOCKER_CONTAINER="$1"
        DOCKER_FILTER="$2"

        echo " -> Clearing configuration"
        listDirectoriesWithFilter "${DOCKER_DIR}/${DOCKER_CONTAINER}" "${DOCKER_FILTER}" | while read DOCKER_DIR; do
            if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
                echo "    - $(relativeDir $DOCKER_DIR)"
                rm -rf "${DOCKER_DIR}/conf/"
            fi
        done
    fi
}

###
 # Deploy configuration
 #
 # Deploy conf/ directory into each docker container
 #
 # $1 -> configuration directory from _provisioning (eg. php/general)
 # $2 -> container name (eg. php)
 # $3 -> sub directory filter (eg. "*" for all or "ubuntu-*" for only ubuntu containers)
 #
 ##
function deployConfiguration() {
    if [[ "$PROVISION" -eq 1 ]]; then
        PROVISION_SUB_DIR="$1"
        DOCKER_CONTAINER="$2"
        DOCKER_FILTER="$3"

        if [ "$DOCKER_FILTER" == "*" ]; then
            echo " -> Deploying configuration"
        else
            echo " -> Deploying configuration with filter '$DOCKER_FILTER'"
        fi

        listDirectoriesWithFilter "${DOCKER_DIR}/${DOCKER_CONTAINER}" "${DOCKER_FILTER}" | while read DOCKER_DIR; do
            if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
                echo "    - $(relativeDir $DOCKER_DIR)"
                cp -f -r "${PROVISION_DIR}/${PROVISION_SUB_DIR}/." "${DOCKER_DIR}/conf/"
            fi
        done
    fi
}

###
 # Header message
 #
 # $1 -> container name (eg. php)
 ##
function header() {
    echo "Building configuration for webdevops/$1"
}

###############################################################################
# MAIN
###############################################################################

## Build bootstrap
[[ $(checkBuildTarget bootstrap) ]] && {
    header "bootstrap"
    buildBaselayout
    deployBaselayout bootstrap          '*'

    # Samson
    deployBaselayout samson-deployment  '*'

    rm -f baselayout.tar
}

## Build base
[[ $(checkBuildTarget base) ]] && {
    header "base"
    clearConfiguration  base  '*'
    deployConfiguration base/general        base  '*'
    deployConfiguration base/centos         base  'centos-*'
    deployConfiguration base/alpine         base  'alpine-*'
}

## Build base-app
[[ $(checkBuildTarget base-app) ]] && {
    header "base-app"
    clearConfiguration  base-app  '*'
    deployConfiguration base-app/general        base-app  '*'
}

## Build apache
[[ $(checkBuildTarget apache) ]] && {
    header "apache"
    clearConfiguration  apache '*'
    deployConfiguration apache/general  apache  '*'
    deployConfiguration apache/centos   apache  'centos-*'
    deployConfiguration apache/alpine   apache  'alpine-*'
}

## Build apache-dev
[[ $(checkBuildTarget apache-dev) ]] && {
    header "apache-dev"
    clearConfiguration  apache-dev '*'
    deployConfiguration apache-dev/general  apache-dev  '*'
}

## Build nginx
[[ $(checkBuildTarget nginx) ]] && {
    header "nginx"
    clearConfiguration  nginx '*'
    deployConfiguration nginx/general  nginx  '*'
    deployConfiguration nginx/centos   nginx  'centos-*'
    deployConfiguration nginx/alpine   nginx  'alpine-*'
}

## Build nginx-dev
[[ $(checkBuildTarget nginx) ]] && {
    header "nginx-dev"
    clearConfiguration  nginx-dev '*'
    deployConfiguration nginx-dev/general  nginx-dev  '*'
}

## Build hhvm
[[ $(checkBuildTarget hhvm) ]] && {
    header "hhvm"
    clearConfiguration  hhvm  '*'
    deployConfiguration hhvm/general  hhvm  '*'
}

## Build hhvm-apache
[[ $(checkBuildTarget hhvm-apache) ]] && {
    header "hhvm-apache"
    clearConfiguration  hhvm-apache  '*'
    deployConfiguration apache/general       hhvm-apache  '*'
    deployConfiguration hhvm-apache/general  hhvm-apache  '*'
}

## Build hhvm-nginx
[[ $(checkBuildTarget hhvm-nginx) ]] && {
    header "hhvm-nginx"
    clearConfiguration  hhvm-nginx  '*'
    deployConfiguration nginx/general       hhvm-nginx  '*'
    deployConfiguration nginx/centos        hhvm-nginx  'centos-*'
    deployConfiguration hhvm-nginx/general  hhvm-nginx  '*'
}

## Build php
[[ $(checkBuildTarget php) ]] && {
    header "php"
    clearConfiguration  php  '*'
    deployConfiguration php/general       php  '*'
    deployConfiguration php/ubuntu-12.04  php  'ubuntu-12.04'
    deployConfiguration php/alpine        php  'alpine-*'

    # deploy php7 configuration to *-php7 containers
    deployConfiguration php/php7          php  '*-php7'
    deployConfiguration php/php7          php  'debian-9'
    deployConfiguration php/php7          php  'ubuntu-16.04'
}

## Build php-apache
[[ $(checkBuildTarget php-apache) ]] && {
    header "php-apache"
    clearConfiguration  php-apache  '*'
    deployConfiguration apache/general      php-apache  '*'
    deployConfiguration apache/centos       php-apache  'centos-*'
    deployConfiguration apache/alpine       php-apache  'alpine-*'
    deployConfiguration php-apache/general  php-apache  '*'
}

## Build php-nginx
[[ $(checkBuildTarget php-nginx) ]] && {
    header "php-nginx"
    clearConfiguration  php-nginx  '*'
    deployConfiguration nginx/general      php-nginx  '*'
    deployConfiguration nginx/centos       php-nginx  'centos-*'
    deployConfiguration nginx/alpine       php-nginx  'alpine-*'
    deployConfiguration php-nginx/general  php-nginx  '*'
}


## Build php-dev
[[ $(checkBuildTarget php-dev) ]] && {
    header "php-dev"
    clearConfiguration  php-dev  '*'
    deployConfiguration php-dev/general   php-dev  '*'
}

## Build php-apache-dev
[[ $(checkBuildTarget php-apache-dev) ]] && {
    header "php-apache-dev"
    clearConfiguration  php-apache-dev  '*'
    deployConfiguration apache/general      php-apache-dev  '*'
    deployConfiguration apache/centos       php-apache-dev  'centos-*'
    deployConfiguration apache/alpine       php-apache-dev  'alpine-*'
    deployConfiguration php-apache/general  php-apache-dev  '*'
    deployConfiguration php-dev/general     php-apache-dev  '*'
    deployConfiguration apache-dev/general  php-apache-dev  '*'
}

## Build php-nginx-dev
[[ $(checkBuildTarget php-nginx-dev) ]] && {
    header "php-nginx-dev"
    clearConfiguration  php-nginx-dev  '*'
    deployConfiguration nginx/general      php-nginx-dev  '*'
    deployConfiguration nginx/centos       php-nginx-dev  'centos-*'
    deployConfiguration nginx/alpine       php-nginx-dev  'alpine-*'
    deployConfiguration php-nginx/general  php-nginx-dev  '*'
    deployConfiguration php-dev/general    php-nginx-dev  '*'
    deployConfiguration nginx-dev/general  php-nginx-dev '*'
}

## Build postfix
[[ $(checkBuildTarget postfix) ]] && {
    header "postfix"
    clearConfiguration  postfix  '*'
    deployConfiguration postfix/general postfix '*'
}

## Build mail-sandbox
[[ $(checkBuildTarget mail-sandbox) ]] && {
    header "mail-sandbox"
    clearConfiguration mail-sandbox  '*'
    deployConfiguration mail-sandbox/general mail-sandbox '*'
}

## Build vsftp
[[ $(checkBuildTarget vsftp) ]] && {
    header "vsftp"
    clearConfiguration  vsftp  '*'
    deployConfiguration vsftp/general vsftp '*'
}

## Build typo3
[[ $(checkBuildTarget typo3) ]] && {
    header "typo3"
    clearConfiguration  typo3  '*'
    deployConfiguration typo3/general  typo3  '*'
}

## Build piwik
[[ $(checkBuildTarget piwik) ]] && {
    header "piwik"
    clearConfiguration  piwik  '*'
    deployConfiguration piwik/general piwik '*'
}

## Build varnish
[[ $(checkBuildTarget varnish) ]] && {
    header "varnish"
    clearConfiguration  varnish  '*'
    deployConfiguration varnish/general varnish '*'
}

## Build samson-deployment
[[ $(checkBuildTarget samson-deployment) ]] && {
    header "samson-deployment"

    # Bootstrap
    buildBaselayout
    deployBaselayout samson-deployment  '*'
    rm -f baselayout.tar

    # Base
    deployConfiguration base/general        samson-deployment 'latest'
    deployConfiguration base-app/general    samson-deployment 'latest'

    # Samson deployment
    deployConfiguration samson-deployment/general        samson-deployment 'latest'
}

## Build cerbot
[[ $(checkBuildTarget certbot) ]] && {
    header "certbot"
}


exit 0
