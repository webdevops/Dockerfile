ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all:
	bash ./run.sh

bootstrap:
	bash ./run.sh bootstrap

ansible:
	bash ./run.sh ansible

base:
	bash ./run.sh base

base-app:
	bash ./run.sh base-app

php:
	bash ./run.sh php

php-dev:
	bash ./run.sh php-dev

apache:
	bash ./run.sh apache

nginx:
	bash ./run.sh nginx

php-apache:
	bash ./run.sh php-apache

php-apache-dev:
	bash ./run.sh php-dev-apache

php-nginx:
	bash ./run.sh php-nginx

php-nginx-dev:
	bash ./run.sh php-dev-nginx

hhvm:
	bash ./run.sh hhvm

hhvm-apache:
	bash ./run.sh hhvm-apache

hhvm-nginx:
	bash ./run.sh hhvm-nginx

postfix:
	bash ./run.sh postfix

vsftp:
	bash ./run.sh vsftp

mail-sandbox:
	bash ./run.sh mail-sandbox

ssh:
	bash ./run.sh ssh
