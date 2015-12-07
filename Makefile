ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
DOCKER_PREFIX="webdevops"
DOCKER_LATEST="ubuntu-14.04"


list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all: base php hhvm service apache nginx

base:    webdevops/bootstrap webdevops/base webdevops/storage
service: webdevops/ssh webdevops/vsftp webdevops/postfix
php:     webdevops/php webdevops/php-apache webdevops/php-nginx
hhvm:    webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

apache:  webdevops/apache webdevops/php-apache webdevops/hhvm-apache
nginx:   webdevops/nginx webdevops/php-nginx webdevops/hhvm-nginx

webdevops/bootstrap:
	bash .bin/build.sh bootstrap "${DOCKER_PREFIX}/bootstrap" "${DOCKER_LATEST}"

webdevops/base:
	bash .bin/build.sh base "${DOCKER_PREFIX}/base" "${DOCKER_LATEST}"

webdevops/php:
	bash .bin/build.sh php "${DOCKER_PREFIX}/php" "${DOCKER_LATEST}"

webdevops/apache:
	bash .bin/build.sh apache "${DOCKER_PREFIX}/apache" "${DOCKER_LATEST}"

webdevops/nginx:
	bash .bin/build.sh nginx "${DOCKER_PREFIX}/nginx" "${DOCKER_LATEST}"

webdevops/php-apache:
	bash .bin/build.sh php-apache "${DOCKER_PREFIX}/php-apache" "${DOCKER_LATEST}"

webdevops/php-nginx:
	bash .bin/build.sh php-nginx "${DOCKER_PREFIX}/php-nginx" "${DOCKER_LATEST}"

webdevops/samson-deployment:
	bash .bin/build.sh samson-deployment "${DOCKER_PREFIX}/samson-deployment" "${DOCKER_LATEST}"

webdevops/hhvm:
	bash .bin/build.sh hhvm "${DOCKER_PREFIX}/hhvm" "${DOCKER_LATEST}"

webdevops/hhvm-apache:
	bash .bin/build.sh hhvm-apache "${DOCKER_PREFIX}/hhvm-apache" "${DOCKER_LATEST}"

webdevops/hhvm-nginx:
	bash .bin/build.sh hhvm-nginx "${DOCKER_PREFIX}/hhvm-nginx" "${DOCKER_LATEST}"

webdevops/ssh:
	bash .bin/build.sh ssh "${DOCKER_PREFIX}/ssh" "${DOCKER_LATEST}"

webdevops/storage:
	bash .bin/build.sh storage "${DOCKER_PREFIX}/storage" "${DOCKER_LATEST}"

webdevops/vsftp:
	bash .bin/build.sh vsftp "${DOCKER_PREFIX}/vsftp" "${DOCKER_LATEST}"

webdevops/postfix:
	bash .bin/build.sh postfix "${DOCKER_PREFIX}/postfix" "${DOCKER_LATEST}"