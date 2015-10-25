ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\/\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

build-all: build-base  build-php build-php-apache build-samson-deployment build-ssh build-storage build-vsftp

build-base:
	bash .bin/build.sh base webdevops/base

build-php:
	bash .bin/build.sh php webdevops/php

build-php-apache:
	bash .bin/build.sh php-apache webdevops/php-apache

build-samson-deployment:
	bash .bin/build.sh samson-deployment webdevops/samson-deployment

build-ssh:
	bash .bin/build.sh ssh webdevops/ssh

build-storage:
	bash .bin/build.sh storage webdevops/storage

build-vsftp:
	bash .bin/build.sh vsftp webdevops/vsftp

