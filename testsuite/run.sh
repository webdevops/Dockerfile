#!/usr/bin/env bash

if [ -n "$1" ]; then
    TEST_TARGET="$1"
else
    TEST_TARGET="all"
fi

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

READLINK='readlink'

[[ `uname` == 'Darwin' ]] && {
	which greadlink > /dev/null && {
		READLINK='greadlink'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils gnu-sed'
		exit 1
	}
}

SCRIPT_DIR=$(dirname $($READLINK -f "$0"))
BASE_DIR=$(dirname "$SCRIPT_DIR")
COLUMNS=$(tput cols)

###
 # Relative dir
 #
 # $1     -> build target (eg. "bootstrap", "base", "php" ...)
 # stdout -> "1" if target is matched
 #
 ##
function checkTestTarget() {
    if [ "$TEST_TARGET" == "all" -o "$TEST_TARGET" == "$1" ]; then
        echo 1
    fi
}


###
 # Init Environment
 #
 ##
function initEnvironment() {
    bundle install --path=vendor
}

###
 # Run test for docker image tag
 #
 # $1     -> Docker tag
 # $2     -> Spec file path
 #
 ##
function runTestForTag() {
    DOCKER_TAG="$1"
    DOCKER_IMAGE_WITH_TAG="${DOCKER_IMAGE}:${DOCKER_TAG}"

    echo ">>> Testing $DOCKER_IMAGE_WITH_TAG with $SPEC_PATH (family: $OS_FAMILY)"

    echo "FROM $DOCKER_IMAGE_WITH_TAG" > "${SCRIPT_DIR}/Dockerfile"

    #docker-compose stop
    #docker-compose rm --force
    #docker-compose build --no-cache
    #docker-compose up -d

    OS_FAMILY="$OS_FAMILY" DOCKER_IMAGE="$DOCKER_IMAGE_WITH_TAG" bundle exec rspec --pattern "$SPEC_PATH"

    rm -f "${SCRIPT_DIR}/Dockerfile"
}

###
 # Set environment OS_FAMILY
 #
 # $1     -> Distribution name for testing
 #
 ##
function setEnvironmentOsFamily() {
    export OS_FAMILY="$1"
}

###
 # Set test spec test file
 #
 # $1     -> Target filename for spec
 #
 ##
function setSpecTest() {
    ## Set test spec path
    SPEC_PATH="spec/docker/${1}_spec.rb"
}

###
 # Setup test environment
 #
 # Sets DOCKER_IMAGE, SPEC_PATH and OS_FAMILY
 #
 # $1     -> Docker image name (without namespace)
 #
 ##
function setupTestEnvironment() {
    echo ""
    printRepeatedChar "="
    echo "=== webdevops/$1"
    printRepeatedChar "="
    echo ""

    ## Set docker image
    DOCKER_IMAGE="webdevops/$1"

    ## Set test spec path
    setSpecTest "$1"

    ## Set default environment
    setEnvironmentOsFamily "ubuntu"
}

function printRepeatedChar() {
    printf "${1}%.0s" $(seq 1 ${COLUMNS})
    echo
}

initEnvironment

#######################################
# webdevops/ansible
#######################################

[[ $(checkTestTarget ansible) ]] && {
    setupTestEnvironment "ansible"

    runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"
}

#######################################
# webdevops/base
#######################################

[[ $(checkTestTarget base) ]] && {
    setupTestEnvironment "base"

    runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"
}

#######################################
# webdevops/php
#######################################

[[ $(checkTestTarget php) ]] && {
    setupTestEnvironment "php"
    setSpecTest "php5"

    runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"

    setEnvironmentOsFamily "debian"
    setSpecTest "php7"
    runTestForTag "debian-8-php7"
}

#######################################
# webdevops/apache
#######################################

[[ $(checkTestTarget apache) ]] && {
    setupTestEnvironment "apache"

    #runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"
}

#######################################
# webdevops/nginx
#######################################

[[ $(checkTestTarget nginx) ]] && {
    setupTestEnvironment "nginx"

    #runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"
}

#######################################
# webdevops/php-apache
#######################################

[[ $(checkTestTarget php-apache) ]] && {
    setupTestEnvironment "php-apache"
    setSpecTest "php5-apache"

    #runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"

    setEnvironmentOsFamily "debian"
    setSpecTest "php7-apache"
    runTestForTag "debian-8-php7"
}

#######################################
# webdevops/php-nginx
#######################################

[[ $(checkTestTarget php-nginx) ]] && {
    setupTestEnvironment "php-nginx"
    setSpecTest "php5-nginx"

    runTestForTag "ubuntu-12.04"
    runTestForTag "ubuntu-14.04"
    runTestForTag "ubuntu-15.04"
    runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    runTestForTag "debian-7"
    runTestForTag "debian-8"

    setEnvironmentOsFamily "debian"
    setSpecTest "php7-nginx"
    runTestForTag "debian-8-php7"
}

#######################################
# webdevops/hhvm
#######################################

[[ $(checkTestTarget hhvm) ]] && {
    setupTestEnvironment "hhvm"
    runTestForTag "latest"
}

#######################################
# webdevops/hhvm-apache
#######################################

[[ $(checkTestTarget hhvm-apache) ]] && {
    setupTestEnvironment "hhvm-apache"
    runTestForTag "latest"
}


#######################################
# webdevops/hhvm-nginx
#######################################

[[ $(checkTestTarget hhvm-nginx) ]] && {
    setupTestEnvironment "hhvm-nginx"
    runTestForTag "latest"
}

#######################################
# webdevops/postfix
#######################################

[[ $(checkTestTarget postfix) ]] && {
    setupTestEnvironment "postfix"
    runTestForTag "latest"
}

#######################################
# webdevops/vsftp
#######################################

[[ $(checkTestTarget vsftp) ]] && {
    setupTestEnvironment "vsftp"
    runTestForTag "latest"

}

#######################################
# webdevops/mail-sandbox
#######################################

[[ $(checkTestTarget mail-sandbox) ]] && {
    setupTestEnvironment "mail-sandbox"
    runTestForTag "latest"

}

#######################################
# webdevops/ssh
#######################################

[[ $(checkTestTarget ssh) ]] && {
    setupTestEnvironment "ssh"
    runTestForTag "latest"

}
