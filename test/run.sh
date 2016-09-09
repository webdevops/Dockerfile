#!/usr/bin/env bash

if [ -n "$1" ]; then
    TEST_TARGET="$1"
else
    TEST_TARGET="all"
fi

if [ -z "$DOCKER_PULL" ]; then
    DOCKER_PULL=0
fi

if [ -z "$FAST" ]; then
    FAST=1
fi

if [ -z "$DEBUG" ]; then
    DEBUG=0
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

SLEEP_TIME=1
SCRIPT_DIR="$(dirname $($READLINK -f "$0"))"
BASE_DIR="$(dirname "$SCRIPT_DIR")"
COLUMNS="$(tput cols)"

DOCKERFILE_EXTRA=""

source "${BASE_DIR}/bin/functions.sh"

cd "$SCRIPT_DIR"

OS_VERSION=""

DOCKER_REPOSITORY="$(cat "$BASE_DIR/DOCKER_REPOSITORY")"
DOCKER_TAG_LATEST="$(cat "$BASE_DIR/DOCKER_TAG_LATEST")"

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

    if [[ -z "$(checkBlacklist "${DOCKER_IMAGE_WITH_TAG}")" ]]; then
        return
    fi

    if [ "$DOCKER_PULL" -eq 1 ]; then
        echo " * Pulling $DOCKER_IMAGE_WITH_TAG from Docker hub ..."
        docker pull "$DOCKER_IMAGE_WITH_TAG"
    fi

    setupDockerTagEnvironment "$DOCKER_TAG"

    DOCKERFILE="Dockerfile.${DOCKER_IMAGE//\//-}-${DOCKER_TAG}.test"

    ## Build Dockerfile
    echo "FROM $DOCKER_IMAGE_WITH_TAG
$DOCKERFILE_EXTRA
COPY conf/ /" > $DOCKERFILE


    if [ "${DEBUG}" -eq 1 ]; then
        echo "DOCKERFILE:"
        echo "-----------"
        echo ">>>"
        cat "$DOCKERFILE" | sed 's/^/     /'
        echo ">>>"
        echo ""
    fi

    if [ "${FAST}" -eq 1 ]; then
        LOGFILE="$(mktemp /tmp/docker.test.XXXXXXXXXX)"

        echo ">> Starting test of ${DOCKER_IMAGE_WITH_TAG}"

        # Run testsuite for docker image
        DOCKERFILE="$DOCKERFILE" OS_FAMILY="$OS_FAMILY" OS_VERSION="$OS_VERSION" DOCKER_IMAGE="$DOCKER_IMAGE_WITH_TAG" bundle exec rspec --pattern " spec/image.rb,$SPEC_PATH" &> $LOGFILE &

        addBackgroundPidToList "Test '$DOCKER_TAG' with spec '$(basename "$SPEC_PATH" _spec.rb)' [family: $OS_FAMILY, version: $OS_VERSION]" "$LOGFILE"
        sleep "$SLEEP_TIME"
    else
        echo ">> Testing '$DOCKER_TAG' with spec '$(basename "$SPEC_PATH" _spec.rb)' [family: $OS_FAMILY, version: $OS_VERSION]"

        # Run testsuite for docker image
        DOCKERFILE="$DOCKERFILE" OS_FAMILY="$OS_FAMILY" OS_VERSION="$OS_VERSION" DOCKER_IMAGE="$DOCKER_IMAGE_WITH_TAG" bundle exec rspec --pattern " spec/image.rb,$SPEC_PATH"
    fi
}

function waitForTestRun() {
    if [ "${FAST}" -eq 1 ]; then
        echo " -> waiting for background testing process..."
        ALWAYS_SHOW_LOGS=1 waitForBackgroundProcesses
    fi

    rm -f Dockerfile.*.test
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
    echo "=== Testing docker image $DOCKER_REPOSITORY/$1"
    printRepeatedChar "="
    echo ""

    ## Set docker image
    DOCKER_IMAGE="$DOCKER_REPOSITORY/$1"

    ## Set test spec path
    setSpecTest "$1"

    ## Set default environment
    setEnvironmentOsFamily "ubuntu"

    ## Reset custom docker environment settings
    DOCKERFILE_CONF=""
    DOCKERFILE_EXTRA=""
}

function printRepeatedChar() {
    printf "${1}%.0s" $(seq 1 50)
    echo
}

###
 # Switch environment variables for test
 #
 # $1     -> Docker tag
 #
 ##
function setupDockerTagEnvironment() {
    unset PHP_REDIS
    unset PHP_APCU
    unset PHP_XDEBUG
    unset PHP_MHASH

    case "$1" in
        ubuntu-12.04)
            export PHP_REDIS=0
            export PHP_APCU=0
            ;;

        debian-7)
            export PHP_REDIS=0
            export PHP_APCU=0
            ;;
            
        debian-8-php7)
            export PHP_XDEBUG=0
            ;;

        alpine-3)
            export PHP_MHASH=0
            ;;

        centos-7-php56)
            export PHP_APCU=0
            export PHP_REDIS=0
            ;;
    esac
}

initEnvironment

#######################################
# webdevops/bootstrap
#######################################

[[ $(checkTestTarget bootstrap) ]] && {
    setupTestEnvironment "bootstrap"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/ansible
#######################################

[[ $(checkTestTarget ansible) ]] && {
    setupTestEnvironment "ansible"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/base
#######################################

[[ $(checkTestTarget base) ]] && {
    setupTestEnvironment "base"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}


#######################################
# webdevops/base
#######################################

[[ $(checkTestTarget base-app) ]] && {
    setupTestEnvironment "base-app"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/php
#######################################

[[ $(checkTestTarget php) ]] && {
    setupTestEnvironment "php"

    ##########
    # PHP 5
    ##########

    setSpecTest "php5"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########

    setSpecTest "php7"

    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}

#######################################
# webdevops/php-dev
#######################################

[[ $(checkTestTarget php-dev) ]] && {
    setupTestEnvironment "php-dev"

    #############################
    # XDEBUG
    #############################

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-dev"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########

    setSpecTest "php7-dev"

    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}

[[ $(checkTestTarget php-dev-blackfire) ]] && {
    setupTestEnvironment "php-dev"

    #############################
    # BLACKFIRE
    #############################

    export PHP_BLACKFIRE=1

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-dev"

    DOCKERFILE_EXTRA="
ENV PHP_DEBUGGER \"blackfire\"
"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    # blackfire not supported on alpine
    #setEnvironmentOsFamily "alpine"
    #OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########

    setSpecTest "php7-dev"

    DOCKERFILE_EXTRA="
ENV PHP_DEBUGGER \"blackfire\"
"

    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    # blackfire not supported on alpine
    #setEnvironmentOsFamily "alpine"
    #OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun

    export PHP_BLACKFIRE=0
}


#######################################
# webdevops/apache
#######################################

[[ $(checkTestTarget apache) ]] && {
    setupTestEnvironment "apache"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/apache-dev
#######################################

[[ $(checkTestTarget apache-dev) ]] && {
    setupTestEnvironment "apache-dev"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/nginx
#######################################

[[ $(checkTestTarget nginx) ]] && {
    setupTestEnvironment "nginx"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}

#######################################
# webdevops/nginx-dev
#######################################

[[ $(checkTestTarget nginx-dev) ]] && {
    setupTestEnvironment "nginx-dev"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun
}


#######################################
# webdevops/php-apache
#######################################

[[ $(checkTestTarget php-apache) ]] && {
    setupTestEnvironment "php-apache"

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-apache"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########

    setEnvironmentOsFamily "ubuntu"
    setSpecTest "php7-apache"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}


#######################################
# webdevops/php-apache-dev
#######################################

[[ $(checkTestTarget php-apache-dev) ]] && {
    setupTestEnvironment "php-apache-dev"

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-apache-dev"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########
    setSpecTest "php7-apache-dev"

    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}

#######################################
# webdevops/php-nginx
#######################################

[[ $(checkTestTarget php-nginx) ]] && {
    setupTestEnvironment "php-nginx"

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-nginx"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########

    setSpecTest "php7-nginx"

    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}


#######################################
# webdevops/php-nginx-dev
#######################################

[[ $(checkTestTarget php-nginx-dev) ]] && {
    setupTestEnvironment "php-nginx-dev"

    ##########
    # PHP 5
    ##########

    setSpecTest "php5-nginx-dev"

    OS_VERSION="12.04" runTestForTag "ubuntu-12.04"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="15.04" runTestForTag "ubuntu-15.04"
    OS_VERSION="15.10" runTestForTag "ubuntu-15.10"

    setEnvironmentOsFamily "redhat"
    OS_VERSION="7" runTestForTag "centos-7"
    OS_VERSION="7" runTestForTag "centos-7-php56"

    setEnvironmentOsFamily "debian"
    OS_VERSION="7" runTestForTag "debian-7"
    OS_VERSION="8" runTestForTag "debian-8"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3"

    waitForTestRun

    ##########
    # PHP 7
    ##########
    setSpecTest "php7-nginx-dev"
    
    setEnvironmentOsFamily "ubuntu"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="8" runTestForTag "debian-8-php7"
    OS_VERSION="testing" runTestForTag "debian-9"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "alpine-3-php7"

    waitForTestRun
}

#######################################
# webdevops/hhvm
#######################################

[[ $(checkTestTarget hhvm) ]] && {
    setupTestEnvironment "hhvm"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"

    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"


    waitForTestRun
}

#######################################
# webdevops/hhvm-apache
#######################################

[[ $(checkTestTarget hhvm-apache) ]] && {
    setupTestEnvironment "hhvm-apache"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"

    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}


#######################################
# webdevops/hhvm-nginx
#######################################

[[ $(checkTestTarget hhvm-nginx) ]] && {
    setupTestEnvironment "hhvm-nginx"
    OS_VERSION="14.04" runTestForTag "ubuntu-14.04"
    OS_VERSION="16.04" runTestForTag "ubuntu-16.04"

    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/postfix
#######################################

[[ $(checkTestTarget postfix) ]] && {
    setupTestEnvironment "postfix"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/vsftp
#######################################

[[ $(checkTestTarget vsftp) ]] && {
    setupTestEnvironment "vsftp"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/mail-sandbox
#######################################

[[ $(checkTestTarget mail-sandbox) ]] && {
    setupTestEnvironment "mail-sandbox"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/ssh
#######################################

[[ $(checkTestTarget ssh) ]] && {
    setupTestEnvironment "ssh"
    OS_VERSION="$DOCKER_TAG_LATEST" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/varnish
#######################################

[[ $(checkTestTarget varnish) ]] && {
    setupTestEnvironment "varnish"

    DOCKERFILE_EXTRA="
ENV VARNISH_BACKEND_HOST \"google.com\"
"

    setEnvironmentOsFamily "alpine"
    OS_VERSION="3" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/sphinx
#######################################

[[ $(checkTestTarget sphinx) ]] && {
    setupTestEnvironment "sphinx"
    setEnvironmentOsFamily "alpine"

    OS_VERSION="3" runTestForTag "latest"

    setEnvironmentOsFamily "debian"
    OS_VERSION="testing" runTestForTag "tex"

    waitForTestRun
}

#######################################
# webdevops/samson-deployment
#######################################

[[ $(checkTestTarget samson-deployment) ]] && {
    setupTestEnvironment "samson-deployment"
    setEnvironmentOsFamily "debian"

    OS_VERSION="8" runTestForTag "latest"

    waitForTestRun
}

#######################################
# webdevops/certbot
#######################################

[[ $(checkTestTarget certbot) ]] && {
    setupTestEnvironment "certbot"

    # setSpecTest "base"
    setEnvironmentOsFamily "alpine"

    OS_VERSION="3" runTestForTag "latest"

    waitForTestRun
}

echo ""
echo " >>> finished, all tests PASSED <<<"
echo ""
