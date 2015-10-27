ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\/\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

all: base php hhvm service

base:    webdevops/base webdevops/storage
service: webdevops/ssh webdevops/vsftp
php:     webdevops/php webdevops/php-apache webdevops/php-nginx
hhvm:    webdevops/hhvm webdevops/hhvm-apache webdevops/hhvm-nginx

apache:  webdevops/php-apache webdevops/hhvm-apache
nginx:   webdevops/php-nginx webdevops/hhvm-nginx

webdevops/base:
	bash .bin/build.sh base webdevops/base

webdevops/php:
	bash .bin/build.sh php webdevops/php

webdevops/php-apache:
	bash .bin/build.sh php-apache webdevops/php-apache

webdevops/php-nginx:
	bash .bin/build.sh php-nginx webdevops/php-nginx

webdevops/samson-deployment:
	bash .bin/build.sh samson-deployment webdevops/samson-deployment

webdevops/hhvm:
	bash .bin/build.sh hhvm webdevops/hhvm

webdevops/hhvm-apache:
	bash .bin/build.sh hhvm-apache webdevops/hhvm-apache

webdevops/hhvm-nginx:
	bash .bin/build.sh hhvm-nginx webdevops/hhvm-nginx

webdevops/ssh:
	bash .bin/build.sh ssh webdevops/ssh

webdevops/storage:
	bash .bin/build.sh storage webdevops/storage

webdevops/vsftp:
	bash .bin/build.sh vsftp webdevops/vsftp

