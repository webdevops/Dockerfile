ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
DOCKER_PREFIX="webdevops"
DOCKER_LATEST="ubuntu-14.04"


list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all:       base php hhvm service apache nginx misc

bootstrap: webdevops/bootstrap
base:      webdevops/bootstrap webdevops/base webdevops/storage
service:   webdevops/ssh webdevops/vsftp webdevops/postfix webdevops/mail-sandbox

php:       webdevops/php webdevops/php-apache webdevops/php-nginx
hhvm:      webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

apache:    webdevops/apache webdevops/php-apache webdevops/hhvm-apache
nginx:     webdevops/nginx webdevops/php-nginx webdevops/hhvm-nginx

misc:      webdevops/typo3

provision:
	bash .bin/provision.sh

webdevops/bootstrap:
	bash .bin/provision.sh bootstrap
	bash .bin/build.sh bootstrap "${DOCKER_PREFIX}/bootstrap" "${DOCKER_LATEST}"

webdevops/base:
	bash .bin/provision.sh base
	bash .bin/build.sh base "${DOCKER_PREFIX}/base" "${DOCKER_LATEST}"

webdevops/php:
	bash .bin/provision.sh php
	bash .bin/build.sh php "${DOCKER_PREFIX}/php" "${DOCKER_LATEST}"

webdevops/apache:
	bash .bin/provision.sh apache
	bash .bin/build.sh apache "${DOCKER_PREFIX}/apache" "${DOCKER_LATEST}"

webdevops/nginx:
	bash .bin/provision.sh nginx
	bash .bin/build.sh nginx "${DOCKER_PREFIX}/nginx" "${DOCKER_LATEST}"

webdevops/php-apache:
	bash .bin/provision.sh php-apache
	bash .bin/build.sh php-apache "${DOCKER_PREFIX}/php-apache" "${DOCKER_LATEST}"

webdevops/php-nginx:
	bash .bin/provision.sh php-nginx
	bash .bin/build.sh php-nginx "${DOCKER_PREFIX}/php-nginx" "${DOCKER_LATEST}"

webdevops/samson-deployment:
	bash .bin/provision.sh samson-deployment
	bash .bin/build.sh samson-deployment "${DOCKER_PREFIX}/samson-deployment" "${DOCKER_LATEST}"

webdevops/hhvm:
	bash .bin/provision.sh hhvm
	bash .bin/build.sh hhvm "${DOCKER_PREFIX}/hhvm" "${DOCKER_LATEST}"

webdevops/hhvm-apache:
	bash .bin/provision.sh hhvm-apache
	bash .bin/build.sh hhvm-apache "${DOCKER_PREFIX}/hhvm-apache" "${DOCKER_LATEST}"

webdevops/hhvm-nginx:
	bash .bin/provision.sh hhvm-nginx
	bash .bin/build.sh hhvm-nginx "${DOCKER_PREFIX}/hhvm-nginx" "${DOCKER_LATEST}"

webdevops/ssh:
	bash .bin/provision.sh ssh
	bash .bin/build.sh ssh "${DOCKER_PREFIX}/ssh" "${DOCKER_LATEST}"

webdevops/storage:
	bash .bin/provision.sh storage
	bash .bin/build.sh storage "${DOCKER_PREFIX}/storage" "${DOCKER_LATEST}"

webdevops/vsftp:
	bash .bin/provision.sh vsftp
	bash .bin/build.sh vsftp "${DOCKER_PREFIX}/vsftp" "${DOCKER_LATEST}"

webdevops/postfix:
	bash .bin/provision.sh postfix
	bash .bin/build.sh postfix "${DOCKER_PREFIX}/postfix" "${DOCKER_LATEST}"

webdevops/mail-sandbox:
	bash .bin/provision.sh mail-sandbox
	bash .bin/build.sh mail-sandbox "${DOCKER_PREFIX}/mail-sandbox" "${DOCKER_LATEST}"

webdevops/typo3:
	bash .bin/provision.sh typo3
	bash .bin/build.sh typo3 "${DOCKER_PREFIX}/typo3" "${DOCKER_LATEST}"

webdevops/piwik:
	bash .bin/provision.sh piwik
	bash .bin/build.sh piwik "${DOCKER_PREFIX}/piwik" "${DOCKER_LATEST}"
