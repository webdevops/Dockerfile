ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
.PHONY: test documentation baselayout provision

DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

full:      provision build
all:       build
build:     bootstrap base web php php-dev hhvm service misc applications

bootstrap: webdevops/bootstrap webdevops/ansible
base:      webdevops/base webdevops/base-app webdevops/storage
service:   webdevops/ssh webdevops/vsftp webdevops/postfix

php:       webdevops/php webdevops/php-apache webdevops/php-nginx 
php-dev:   webdevops/php-dev webdevops/php-apache-dev webdevops/php-nginx-dev
hhvm:      webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

web:       webdevops/apache webdevops/apache-dev webdevops/nginx webdevops/nginx-dev webdevops/varnish webdevops/certbot

applications: webdevops/typo3 webdevops/piwik

misc:      webdevops/mail-sandbox webdevops/sphinx

test:
	cd "test/" && make all

test-hub-images:
	DOCKER_PULL=1 make test

baselayout:
	BASELAYOUT=1 PROVISION=0 bash bin/provision.sh

provision:
	python bin/buildDockerfile.py --template=template/ --dockerfile=docker/
	BASELAYOUT=0 PROVISION=1 bash bin/provision.sh

publish:    dist-update rebuild test push

dist-update:
	docker pull centos:7
	docker pull ubuntu:12.04
	docker pull ubuntu:14.04
	docker pull ubuntu:15.04
	docker pull ubuntu:15.10
	docker pull ubuntu:16.04
	docker pull debian:7
	docker pull debian:8
	docker pull debian:stretch
	docker pull alpine:3.4

rebuild:
	# Rebuild all containers but use caching for duplicates
	# Bootstrap
	FORCE=1 make webdevops/bootstrap
	FORCE=0 make webdevops/ansible
	# Base
	FORCE=1 make webdevops/base
	FORCE=1 make webdevops/base-app
	FORCE=0 make webdevops/storage
	# Other containers
	FORCE=1 make web
	FORCE=1 make php
	FORCE=1 make hhvm
	FORCE=1 make service
	FORCE=1 make misc
	FORCE=1 make applications

push:
	BUILD_MODE=push make all

setup:
	pip install --upgrade -I -r ./requirements.txt

graph:
	python ./bin/diagram.py  --dockerfile docker/ --filename documentation/docs/resources/images/docker-image-layout.gv

graph-full:
	python ./bin/diagram.py  --all --dockerfile docker/ --filename documentation/docs/resources/images/docker-image-full-layout.gv

documentation:
	docker run -t -i --rm -p 8080:8000 -v "$$(pwd)/documentation/docs/:/opt/docs" webdevops/sphinx sphinx-autobuild --poll -H 0.0.0.0 /opt/docs html

documentation-pdf:
	docker run -t -i --rm -p 8080:8000 -v "$$(pwd)/documentation/docs/:/opt/docs" -w /opt/docs webdevops/sphinx:tex make latexpdf

webdevops/bootstrap:
	bash bin/build.sh bootstrap "${DOCKER_REPOSITORY}/bootstrap" "${DOCKER_TAG_LATEST}"

webdevops/ansible:
	bash bin/build.sh bootstrap "${DOCKER_REPOSITORY}/ansible" "${DOCKER_TAG_LATEST}"

webdevops/base:
	bash bin/build.sh base "${DOCKER_REPOSITORY}/base" "${DOCKER_TAG_LATEST}"

webdevops/base-app:
	bash bin/build.sh base-app "${DOCKER_REPOSITORY}/base-app" "${DOCKER_TAG_LATEST}"

webdevops/php:
	bash bin/build.sh php "${DOCKER_REPOSITORY}/php" "${DOCKER_TAG_LATEST}"

webdevops/php-dev:
	bash bin/build.sh php-dev "${DOCKER_REPOSITORY}/php-dev" "${DOCKER_TAG_LATEST}"

webdevops/apache:
	bash bin/build.sh apache "${DOCKER_REPOSITORY}/apache" "${DOCKER_TAG_LATEST}"

webdevops/apache-dev:
	bash bin/build.sh apache-dev "${DOCKER_REPOSITORY}/apache-dev" "${DOCKER_TAG_LATEST}"

webdevops/nginx:
	bash bin/build.sh nginx "${DOCKER_REPOSITORY}/nginx" "${DOCKER_TAG_LATEST}"

webdevops/nginx-dev:
	bash bin/build.sh nginx-dev "${DOCKER_REPOSITORY}/nginx-dev" "${DOCKER_TAG_LATEST}"

webdevops/php-apache:
	bash bin/build.sh php-apache "${DOCKER_REPOSITORY}/php-apache" "${DOCKER_TAG_LATEST}"

webdevops/php-apache-dev:
	bash bin/build.sh php-apache-dev "${DOCKER_REPOSITORY}/php-apache-dev" "${DOCKER_TAG_LATEST}"

webdevops/php-nginx:
	bash bin/build.sh php-nginx "${DOCKER_REPOSITORY}/php-nginx" "${DOCKER_TAG_LATEST}"

webdevops/php-nginx-dev:
	bash bin/build.sh php-nginx-dev "${DOCKER_REPOSITORY}/php-nginx-dev" "${DOCKER_TAG_LATEST}"

webdevops/hhvm:
	bash bin/build.sh hhvm "${DOCKER_REPOSITORY}/hhvm" "${DOCKER_TAG_LATEST}"

webdevops/hhvm-apache:
	bash bin/build.sh hhvm-apache "${DOCKER_REPOSITORY}/hhvm-apache" "${DOCKER_TAG_LATEST}"

webdevops/hhvm-nginx:
	bash bin/build.sh hhvm-nginx "${DOCKER_REPOSITORY}/hhvm-nginx" "${DOCKER_TAG_LATEST}"

webdevops/ssh:
	bash bin/build.sh ssh "${DOCKER_REPOSITORY}/ssh" "${DOCKER_TAG_LATEST}"

webdevops/storage:
	bash bin/build.sh storage "${DOCKER_REPOSITORY}/storage" "${DOCKER_TAG_LATEST}"

webdevops/vsftp:
	bash bin/build.sh vsftp "${DOCKER_REPOSITORY}/vsftp" "${DOCKER_TAG_LATEST}"

webdevops/postfix:
	bash bin/build.sh postfix "${DOCKER_REPOSITORY}/postfix" "${DOCKER_TAG_LATEST}"

webdevops/mail-sandbox:
	bash bin/build.sh mail-sandbox "${DOCKER_REPOSITORY}/mail-sandbox" "${DOCKER_TAG_LATEST}"

webdevops/typo3:
	bash bin/build.sh typo3 "${DOCKER_REPOSITORY}/typo3" "${DOCKER_TAG_LATEST}"

webdevops/piwik:
	bash bin/build.sh piwik "${DOCKER_REPOSITORY}/piwik" "${DOCKER_TAG_LATEST}"

webdevops/samson-deployment:
	bash bin/build.sh samson-deployment "${DOCKER_REPOSITORY}/samson-deployment" "${DOCKER_TAG_LATEST}"

webdevops/sphinx:
	bash bin/build.sh sphinx "${DOCKER_REPOSITORY}/sphinx" "${DOCKER_TAG_LATEST}"

webdevops/varnish:
	bash bin/build.sh varnish "${DOCKER_REPOSITORY}/varnish" "${DOCKER_TAG_LATEST}"

webdevops/certbot:
	bash bin/build.sh certbot "${DOCKER_REPOSITORY}/certbot" "${DOCKER_TAG_LATEST}"
