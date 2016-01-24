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
 # Build configuration
 #
 # Build tar file from _localscripts for bootstrap containers
 #
 ##
function buildLocalscripts() {
    echo " * Building localscripts"

    cd "${LOCALSCRIPT_DIR}"
    rm -f scripts.tar
    tar -jmcf scripts.tar *

    listDirectories "$BASE_DIR/bootstrap"  | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp scripts.tar "${DOCKER_DIR}/scripts.tar"
        fi
    done

    rm -f scripts.tar
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
    DOCKER_CONTAINER="$1"
    DOCKER_FILTER="$2"

    echo " -> Clearing configuration"
    listDirectoriesWithFilter "${BASE_DIR}/${DOCKER_CONTAINER}" "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            rm -rf "${DOCKER_DIR}/conf/"
        fi
    done
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
function deploConfiguration() {
    PROVISION_SUB_DIR="$1"
    DOCKER_CONTAINER="$2"
    DOCKER_FILTER="$3"

    if [ "$DOCKER_FILTER" == "*" ]; then
        echo " -> Deploying configuration"
    else
        echo " -> Deploying configuration with filter '$DOCKER_FILTER'"
    fi

    listDirectoriesWithFilter "${BASE_DIR}/${DOCKER_CONTAINER}" "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp -f -r "${PROVISION_DIR}/${PROVISION_SUB_DIR}/." "${DOCKER_DIR}/conf/"
        fi
    done
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
    buildLocalscripts
}

## Build base
[[ $(checkBuildTarget base) ]] && {
    header "base"
    clearConfiguration  base  '*'
    deploConfiguration base/general        base  '*'
    deploConfiguration base/centos         base  'centos-*'
}

## Build apache
[[ $(checkBuildTarget apache) ]] && {
    header "apache"
    clearConfiguration  apache '*'
    deploConfiguration apache/general  apache  '*'
    deploConfiguration apache/centos   apache  'centos-*'
}

## Build nginx
[[ $(checkBuildTarget nginx) ]] && {
    header "nginx"
    clearConfiguration  nginx '*'
    deploConfiguration nginx/general  nginx  '*'
    deploConfiguration nginx/centos   nginx  'centos-*'
}

## Build hhvm
[[ $(checkBuildTarget hhvm) ]] && {
    header "hhvm"
    clearConfiguration  hhvm  '*'
    deploConfiguration hhvm/general  hhvm  '*'
}

## Build hhvm-apache
[[ $(checkBuildTarget hhvm-apache) ]] && {
    header "hhvm-apache"
    clearConfiguration  hhvm-apache  '*'
    deploConfiguration apache/general       hhvm-apache  '*'
    deploConfiguration hhvm-apache/general  hhvm-apache  '*'
}

## Build hhvm-nginx
[[ $(checkBuildTarget hhvm-nginx) ]] && {
    header "hhvm-nginx"
    clearConfiguration  hhvm-nginx  '*'
    deploConfiguration nginx/general       hhvm-nginx  '*'
    deploConfiguration nginx/centos        hhvm-nginx  'centos-*'
    deploConfiguration hhvm-nginx/general  hhvm-nginx  '*'
}

## Build php
[[ $(checkBuildTarget php) ]] && {
    header "php"
    clearConfiguration  php  '*'
    deploConfiguration php/general       php  '*'
    deploConfiguration php/ubuntu-12.04  php  'ubuntu-12.04'

    clearConfiguration  php  'debian-*-php7'
    deploConfiguration php/debian-php7  php  'debian-*-php7'
}

## Build php-apache
[[ $(checkBuildTarget php-apache) ]] && {
    header "php-apache"
    clearConfiguration  php-apache  '*'
    deploConfiguration apache/general      php-apache  '*'
    deploConfiguration apache/centos       php-apache  'centos-*'
    deploConfiguration php-apache/general  php-apache  '*'
}

## Build php-nginx
[[ $(checkBuildTarget php-nginx) ]] && {
    header "php-nginx"
    clearConfiguration  php-nginx  '*'
    deploConfiguration nginx/general      php-nginx  '*'
    deploConfiguration nginx/centos       php-nginx  'centos-*'
    deploConfiguration php-nginx/general  php-nginx  '*'
}

## Build postfix
[[ $(checkBuildTarget postfix) ]] && {
    header "postfix"
    clearConfiguration  postfix  '*'
    deploConfiguration postfix/general postfix '*'
}

## Build mail-sandbox
[[ $(checkBuildTarget mail-sandbox) ]] && {
    header "postfix"
    clearConfiguration mail-sandbox  '*'
    deploConfiguration mail-sandbox/general mail-sandbox '*'
}

## Build vsftp
[[ $(checkBuildTarget vsftp) ]] && {
    header "vsftp"
    clearConfiguration  vsftp  '*'
    deploConfiguration vsftp/general vsftp '*'
}

## Build typo3
[[ $(checkBuildTarget typo3) ]] && {
    header "typo3"
    clearConfiguration  typo3  '*'
    deploConfiguration typo3/general  typo3  '*'
}

## Build piwik
[[ $(checkBuildTarget piwik) ]] && {
    header "piwik"
    clearConfiguration  piwik  '*'
    deploConfiguration piwik/general piwik '*'
}

exit 0
