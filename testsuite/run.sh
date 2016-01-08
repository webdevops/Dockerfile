#!/usr/bin/env bash

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


function initEnvironment() {
    bundle install --path=vendor
}

function runTest() {
    DOCKER_IMAGE="$1"
    SPEC_PATH="$2"

    echo ">>> Testing $DOCKER_IMAGE with $SPEC_PATH"

    echo "FROM $DOCKER_IMAGE" > "${SCRIPT_DIR}/Dockerfile"

    #docker-compose stop
    #docker-compose rm --force
    #docker-compose build --no-cache
    #docker-compose up -d

    TARGET="$DOCKER_IMAGE" bundle exec rspec --pattern "$SPEC_PATH"

    rm -f "${SCRIPT_DIR}/Dockerfile"
}


initEnvironment

#######################################
# webdevops/base
#######################################

runTest     "webdevops/base:ubuntu-12.04"    "spec/base/ubuntu_spec.rb"
runTest     "webdevops/base:ubuntu-14.04"    "spec/base/ubuntu_spec.rb"
runTest     "webdevops/base:ubuntu-15.04"    "spec/base/ubuntu_spec.rb"
runTest     "webdevops/base:ubuntu-15.10"    "spec/base/ubuntu_spec.rb"

runTest     "webdevops/base:centos-7"        "spec/base/centos_spec.rb"

runTest     "webdevops/base:debian-7"        "spec/base/debian_spec.rb"
runTest     "webdevops/base:debian-8"        "spec/base/debian_spec.rb"

#######################################
# webdevops/php
#######################################

runTest     "webdevops/php:ubuntu-12.04"    "spec/php/ubuntu_spec.rb"
runTest     "webdevops/php:ubuntu-14.04"    "spec/php/ubuntu_spec.rb"
runTest     "webdevops/php:ubuntu-15.04"    "spec/php/ubuntu_spec.rb"
runTest     "webdevops/php:ubuntu-15.10"    "spec/php/ubuntu_spec.rb"

runTest     "webdevops/php:centos-7"        "spec/php/centos_spec.rb"

runTest     "webdevops/php:debian-7"        "spec/php/debian_spec.rb"
runTest     "webdevops/php:debian-8"        "spec/php/debian_spec.rb"
