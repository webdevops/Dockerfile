MAKEFLAGS += --silent
.PHONY: list setup requirements structure-test provision

list:
	sh -c "echo; $(MAKE) -p no_targets__ 2>/dev/null |\
		awk -F':' '/^[a-zA-Z0-9][^\$$#\\t=]*:([^=]|$$)/ {split(\$$1,A,/ /);\
		for(i in A)print A[i]}' | \
	  	grep -v '__\$$\|Makefile\|%' | \
		sort"

setup:     requirements

requirements:
	pip install --upgrade -I -r ./bin/requirements.txt
	cd tests/serverspec && bundle install --path=vendor

structure-test:
	cd tests/structure-test && ./run.sh

provision:
	python2 bin/console generate:dockerfile
	python2 bin/console generate:provision
