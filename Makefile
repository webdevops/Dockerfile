ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent
.PHONY: test documentation baselayout provision

DOCKER_REPOSITORY=`cat DOCKER_REPOSITORY`
DOCKER_TAG_LATEST=`cat DOCKER_TAG_LATEST`

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

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

requirements:
	pip install -r ./requirements.txt

test:
	python bin/console docker:test

old-test:
	test/run.sh


baselayout:
	python bin/console generate:provision --baselayout

provision:
	python bin/console generate:dockerfile
	python bin/console generate:provision

publish:    dist-update rebuild test old-test push

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

push:
	python ./bin/console docker:push --threads=auto

setup:
	pip install --upgrade -I -r ./requirements.txt

graph:
	python ./bin/console generate:graph

graph-full:
	python ./bin/console generate:graph --all --filename docker-image-full-layout.gv

documentation:
	docker run -t -i --rm -p 8080:8000 -v "$$(pwd)/documentation/docs/:/opt/docs" -e "VIRTUAL_HOST=documentation.docker" -e "VIRTUAL_PORT=8000" webdevops/sphinx sphinx-autobuild --poll -H 0.0.0.0 /opt/docs html

webdevops/bootstrap:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/bootstrap

webdevops/ansible:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/bootstrap

webdevops/base:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/base

webdevops/base-app:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/base-app

webdevops/php:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php

webdevops/php-dev:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php-dev

webdevops/apache:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/apache

webdevops/apache-dev:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/apache-dev

webdevops/nginx:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/nginx

webdevops/nginx-dev:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/nginx-dev

webdevops/php-apache:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php-apache

webdevops/php-apache-dev:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php-apache-dev

webdevops/php-nginx:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php-nginx

webdevops/php-nginx-dev:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/php-nginx-dev

webdevops/hhvm:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/hhvm

webdevops/hhvm-apache:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/hhvm-apache

webdevops/hhvm-nginx:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/hhvm-nginx

webdevops/ssh:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/ssh

webdevops/storage:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/storage

webdevops/vsftp:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/vsftp

webdevops/postfix:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/postfix

webdevops/mail-sandbox:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/mail-sandbox

webdevops/typo3:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/typo3

webdevops/piwik:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/piwik

webdevops/samson-deployment:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/samson-deployment

webdevops/sphinx:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/sphinx

webdevops/varnish:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/varnish

webdevops/certbot:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/certbot

webdevops/liquibase:
	python ./bin/console docker:build --threads=auto --whitelist=webdevops/liquibase
