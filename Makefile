ARGS = $(filter-out $@,$(MAKECMDGOALS))
MAKEFLAGS += --silent

list:
	sh -c "echo; $(MAKE) -p no_targets__ | awk -F':' '/^[a-zA-Z0-9][^\$$#\/\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);for(i in A)print A[i]}' | grep -v '__\$$' | grep -v 'Makefile'| sort"

webdevops: webdevops/base  webdevops/php webdevops/php-apache webdevops/samson-deployment webdevops/ssh webdevops/storage webdevops/vsftp

webdevops/base:
	bash .bin/build.sh base webdevops/base

webdevops/php:
	bash .bin/build.sh php webdevops/php

webdevops/php-apache:
	bash .bin/build.sh php-apache webdevops/php-apache

webdevops/samson-deployment:
	bash .bin/build.sh samson-deployment webdevops/samson-deployment

webdevops/ssh:
	bash .bin/build.sh ssh webdevops/ssh

webdevops/storage:
	bash .bin/build.sh storage webdevops/storage

webdevops/vsftp:
	bash .bin/build.sh vsftp webdevops/vsftp

