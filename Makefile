ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all:       bootstrap base web php hhvm service misc applications

bootstrap: webdevops/bootstrap webdevops/ansible
base:      webdevops/base webdevops/storage
service:   webdevops/ssh webdevops/vsftp webdevops/postfix

php:       webdevops/php webdevops/php-apache webdevops/php-nginx
hhvm:      webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

web:       webdevops/apache webdevops/nginx

applications: webdevops/typo3 webdevops/piwik

misc:      webdevops/mail-sandbox

test:
	cd "testsuite/" && bash run.sh all

test-hub-images:
	DOCKER_PULL=1 make test

provision:
	bash .bin/provision.sh

publish:
	rm -f publish.log
	make dist-update | tee --append publish.log
	make rebuild | tee --append publish.log
	make push | tee --append publish.log

dist-update:
	docker pull centos:7
	docker pull ubuntu:12.04
	docker pull ubuntu:14.04
	docker pull ubuntu:15.04
	docker pull ubuntu:15.10
	docker pull debian:7
	docker pull debian:8

rebuild:
	# Rebuild all containers but use caching
	# Bootstrap
	FORCE=1 make webdevops/bootstrap
	FORCE=0 make webdevops/ansible
	# Base
	FORCE=1 make webdevops/base
	FORCE=0 make webdevops/storage
	# Other containers
	FORCE=1 make web
	FORCE=1 make php
	FORCE=1 make hhvm
	FORCE=1 make service
	FORCE=1 make misc
	FORCE=1 make applications

push:
	make test
	DOCKER_PUSH=1 make all

webdevops/bootstrap:
	bash .bin/provision.sh bootstrap
	bash .bin/build.sh bootstrap "${DOCKER_REPOSITORY}/bootstrap" "${DOCKER_TAG_LATEST}"

webdevops/ansible:
	bash .bin/provision.sh bootstrap
	bash .bin/build.sh bootstrap "${DOCKER_REPOSITORY}/ansible" "${DOCKER_TAG_LATEST}"

webdevops/base:
	bash .bin/provision.sh base
	bash .bin/build.sh base "${DOCKER_REPOSITORY}/base" "${DOCKER_TAG_LATEST}"

webdevops/php:
	bash .bin/provision.sh php
	bash .bin/build.sh php "${DOCKER_REPOSITORY}/php" "${DOCKER_TAG_LATEST}"

webdevops/apache:
	bash .bin/provision.sh apache
	bash .bin/build.sh apache "${DOCKER_REPOSITORY}/apache" "${DOCKER_TAG_LATEST}"

webdevops/nginx:
	bash .bin/provision.sh nginx
	bash .bin/build.sh nginx "${DOCKER_REPOSITORY}/nginx" "${DOCKER_TAG_LATEST}"

webdevops/php-apache:
	bash .bin/provision.sh php-apache
	bash .bin/build.sh php-apache "${DOCKER_REPOSITORY}/php-apache" "${DOCKER_TAG_LATEST}"

webdevops/php-nginx:
	bash .bin/provision.sh php-nginx
	bash .bin/build.sh php-nginx "${DOCKER_REPOSITORY}/php-nginx" "${DOCKER_TAG_LATEST}"

webdevops/samson-deployment:
	bash .bin/provision.sh samson-deployment
	bash .bin/build.sh samson-deployment "${DOCKER_REPOSITORY}/samson-deployment" "${DOCKER_TAG_LATEST}"

webdevops/hhvm:
	bash .bin/provision.sh hhvm
	bash .bin/build.sh hhvm "${DOCKER_REPOSITORY}/hhvm" "${DOCKER_TAG_LATEST}"

webdevops/hhvm-apache:
	bash .bin/provision.sh hhvm-apache
	bash .bin/build.sh hhvm-apache "${DOCKER_REPOSITORY}/hhvm-apache" "${DOCKER_TAG_LATEST}"

webdevops/hhvm-nginx:
	bash .bin/provision.sh hhvm-nginx
	bash .bin/build.sh hhvm-nginx "${DOCKER_REPOSITORY}/hhvm-nginx" "${DOCKER_TAG_LATEST}"

webdevops/ssh:
	bash .bin/provision.sh ssh
	bash .bin/build.sh ssh "${DOCKER_REPOSITORY}/ssh" "${DOCKER_TAG_LATEST}"

webdevops/storage:
	bash .bin/provision.sh storage
	bash .bin/build.sh storage "${DOCKER_REPOSITORY}/storage" "${DOCKER_TAG_LATEST}"

webdevops/vsftp:
	bash .bin/provision.sh vsftp
	bash .bin/build.sh vsftp "${DOCKER_REPOSITORY}/vsftp" "${DOCKER_TAG_LATEST}"

webdevops/postfix:
	bash .bin/provision.sh postfix
	bash .bin/build.sh postfix "${DOCKER_REPOSITORY}/postfix" "${DOCKER_TAG_LATEST}"

webdevops/mail-sandbox:
	bash .bin/provision.sh mail-sandbox
	bash .bin/build.sh mail-sandbox "${DOCKER_REPOSITORY}/mail-sandbox" "${DOCKER_TAG_LATEST}"

webdevops/typo3:
	bash .bin/provision.sh typo3
	bash .bin/build.sh typo3 "${DOCKER_REPOSITORY}/typo3" "${DOCKER_TAG_LATEST}"

webdevops/piwik:
	bash .bin/provision.sh piwik
	bash .bin/build.sh piwik "${DOCKER_REPOSITORY}/piwik" "${DOCKER_TAG_LATEST}"
