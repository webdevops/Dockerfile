ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all:       base php hhvm service apache nginx misc

bootstrap: webdevops/bootstrap webdevops/ansible
base:      webdevops/bootstrap webdevops/base webdevops/storage
service:   webdevops/ssh webdevops/vsftp webdevops/postfix webdevops/mail-sandbox

php:       webdevops/php webdevops/php-apache webdevops/php-nginx
hhvm:      webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

apache:    webdevops/apache webdevops/php-apache webdevops/hhvm-apache
nginx:     webdevops/nginx webdevops/php-nginx webdevops/hhvm-nginx

misc:      webdevops/typo3

test:
	cd "testsuite/" && bash run.sh all

test-with-pull:
	cd "testsuite/" && DOCKER_PULL=1 bash run.sh all

provision:
	bash .bin/provision.sh

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
