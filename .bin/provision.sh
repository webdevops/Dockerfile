#!/usr/bin/env bash

if [ -n "$1" ]; then
    BUILD_TARGET="$1"
else
    BUILD_TARGET="all"
fi


set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

# Fix readlink issue on macos

READLINK='readlink'

[[ `uname` == 'Darwin' ]] && {
	which greadlink > /dev/null && {
		READLINK='greadlink'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils gnu-sed'
		exit 1
	}
}

SCRIPT_DIR=$(dirname "$($READLINK -f "$0")")
BASE_DIR=$(dirname "$SCRIPT_DIR")

LOCALSCRIPT_DIR="${BASE_DIR}/_localscripts"
PROVISION_DIR="${BASE_DIR}/_provisioning"


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

#######################################
# Localscripts
#######################################

###
 # Build localscripts
 #
 # Build tar file from _localscripts for bootstrap containers
 #
 ##
function buildLocalscripts() {
    echo " * Building localscripts"

    cd "${LOCALSCRIPT_DIR}"
    rm -f scripts.tar
    tar -jmcf scripts.tar *

    find "$BASE_DIR/bootstrap" -type d -depth 1 | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp scripts.tar "${DOCKER_DIR}/scripts.tar"
        fi
    done

    rm -f scripts.tar
}

#######################################
# Provision
#######################################

###
 # Clear provisioning
 #
 # Clear conf/ directory of each docker container
 #
 # $1 -> container name (eg. php)
 # $2 -> sub directory filter (eg. "*" for all or "ubuntu-*" for only ubuntu containers)
 #
 ##
function clearProvision() {
    DOCKER_CONTAINER="$1"
    DOCKER_FILTER="$2"

    echo " * Clearing provision"
    find "${BASE_DIR}/${DOCKER_CONTAINER}" -type d -depth 1 -iname "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            rm -rf "${DOCKER_DIR}/conf/"
        fi
    done
}

###
 # Deploy provisioning
 #
 # Deploy conf/ directory into each docker container
 #
 # $1 -> configuration directory from _provisioning (eg. php/general)
 # $2 -> container name (eg. php)
 # $3 -> sub directory filter (eg. "*" for all or "ubuntu-*" for only ubuntu containers)
 #
 ##
function deployProvision() {
    PROVISION_SUB_DIR="$1"
    DOCKER_CONTAINER="$2"
    DOCKER_FILTER="$3"

    if [ "$DOCKER_FILTER" == "*" ]; then
        echo " * Deploying provision"
    else
        echo " * Deploying provision with filter '$DOCKER_FILTER'"
    fi

    find "${BASE_DIR}/${DOCKER_CONTAINER}" -type d -depth 1 -iname "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp -f -r "${PROVISION_DIR}/${PROVISION_SUB_DIR}/" "${DOCKER_DIR}/conf"
        fi
    done
}

###############################################################################
# MAIN
###############################################################################


## Build bootstrap
[[ $(checkBuildTarget bootstrap) ]] && {
    echo "Building provision for webdevops/bootstrap..."
    buildLocalscripts
}

## Build base
[[ $(checkBuildTarget base) ]] && {
    echo "Building provision for webdevops/base..."
    clearProvision  base  '*'
    deployProvision base/general        base  '*'
    deployProvision base/centos         base  'centos-*'
}

## Build apache
[[ $(checkBuildTarget apache) ]] && {
    echo "Building provision for webdevops/apache..."
    clearProvision  apache '*'
    deployProvision apache/general  apache  '*'
    deployProvision apache/centos   apache  'centos-*'
}

## Build nginx
[[ $(checkBuildTarget nginx) ]] && {
    echo "Building provision for webdevops/nginx..."
    clearProvision  nginx '*'
    deployProvision nginx/general  nginx  '*'
    deployProvision nginx/centos   nginx  'centos-*'
}

## Build hhvm
[[ $(checkBuildTarget hhvm) ]] && {
    echo "Building provision for webdevops/hhvm..."
    clearProvision  hhvm  '*'
    deployProvision hhvm/general  hhvm  '*'
}

## Build hhvm-apache
[[ $(checkBuildTarget hhvm-apache) ]] && {
    echo "Building provision for webdevops/hhvm-apache..."
    clearProvision  hhvm-apache  '*'
    deployProvision apache/general       hhvm-apache  '*'
    deployProvision hhvm-apache/general  hhvm-apache  '*'
}

## Build hhvm-nginx
[[ $(checkBuildTarget hhvm-nginx) ]] && {
    echo "Building provision for webdevops/hhvm-nginx..."
    clearProvision  hhvm-nginx  '*'
    deployProvision nginx/general       hhvm-nginx  '*'
    deployProvision nginx/centos        hhvm-nginx  'centos-*'
    deployProvision hhvm-nginx/general  hhvm-nginx  '*'
}

## Build php
[[ $(checkBuildTarget php) ]] && {
    echo "Building provision for webdevops/php..."
    clearProvision  php  '*'
    deployProvision php/general       php  '*'
    deployProvision php/ubuntu-12.04  php  'ubuntu-12.04'

    clearProvision  php  'debian-*-php7'
    deployProvision php/debian-php7  php  'debian-*-php7'
}

## Build php-apache
[[ $(checkBuildTarget php-apache) ]] && {
    echo "Building provision for webdevops/php-apache..."
    clearProvision  php-apache  '*'
    deployProvision apache/general      php-apache  '*'
    deployProvision apache/centos       php-apache  'centos-*'
    deployProvision php-apache/general  php-apache  '*'
    deployProvision php-apache/debian-php7  php-apache  'debian-*-php7'
}

## Build php-nginx
[[ $(checkBuildTarget php-nginx) ]] && {
    echo "Building provision for webdevops/php-nginx..."
    clearProvision  php-nginx  '*'
    deployProvision nginx/general      php-nginx  '*'
    deployProvision nginx/centos       php-nginx  'centos-*'
    deployProvision php-nginx/general  php-nginx  '*'
    deployProvision php-nginx/debian-php7  php-nginx  'debian-*-php7'
}

## Build typo3
[[ $(checkBuildTarget typo3) ]] && {
    echo "Building provision for webdevops/typo3..."
    clearProvision  typo3  '*'
    deployProvision typo3/general  typo3  '*'
}

exit 0
