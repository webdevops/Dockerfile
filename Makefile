ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
.PHONY: test documentation baselayout provision

DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ |\
		awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);\
		for(i in A)print A[i]}' | \
	  	grep -v '__\$$\|Makefile\|%' | \
		sort"

full:      provision build

all:       build

build:
	python ./bin/console docker:build --threads=auto

bootstrap: webdevops/bootstrap webdevops/ansible
base:      webdevops/base webdevops/base-app webdevops/storage
service:   webdevops/ssh webdevops/vsftp webdevops/postfix

php:       webdevops/php webdevops/php-apache webdevops/php-nginx
php-dev:   webdevops/php-dev webdevops/php-apache-dev webdevops/php-nginx-dev
hhvm:      webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

web:       webdevops/apache webdevops/apache-dev webdevops/nginx webdevops/nginx-dev webdevops/varnish webdevops/certbot

applications: webdevops/typo3 webdevops/piwik

misc:      webdevops/mail-sandbox webdevops/sphinx webdevops/liquibase

setup:     requirements

requirements:
	pip install --upgrade -I -r ./bin/requirements.txt
	cd tests/serverspec && bundle install --path=vendor

test:
	python bin/console test:serverspec --threads=auto -v

baselayout:
	python bin/console generate:provision --baselayout

provision:
	python bin/console generate:dockerfile
	python bin/console generate:provision

publish:    dist-update rebuild test old-test push

push:
	python ./bin/console docker:push --threads=auto

graph:
	python ./bin/console generate:graph

graph-full:
	python ./bin/console generate:graph --all\
		--filename docker-image-full-layout.gv

documentation:
	docker run -t -i --rm -p 8000 \
		-v "$$(pwd)/documentation/docs/:/opt/docs" \
		-e "VIRTUAL_HOST=documentation.docker" \
		-e "VIRTUAL_PORT=8000" \
		webdevops/sphinx sphinx-autobuild \
		--poll -H 0.0.0.0 /opt/docs html

webdevops/%:
	python ./bin/console docker:build --threads=auto --whitelist=$@
