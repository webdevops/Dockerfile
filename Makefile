MAKEFLAGS += --silent
.PHONY: setup requirements structure-test provision graph graph-full

setup:     requirements

requirements:
	pip install --upgrade -I -r ./bin/requirements.txt
	cd tests/serverspec && bundle install --path=vendor

structure-test:
	cd tests/structure-test && ./run.sh

provision:
	python2 bin/console generate:dockerfile
	python2 bin/console generate:provision

graph:
	python2 ./bin/console generate:graph

graph-full:
	python2 ./bin/console generate:graph --all\
		--filename docker-image-full-layout.gv
