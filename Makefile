MAKEFLAGS += --silent
.PHONY: generate check test test-image test-image-runner test-image-run structure-test

# The containers run as the user who calls make, so that the files they write belong to them. In rootless Docker,
# root in the container already is that user. sh replaces the entrypoint of webdevops/php, which would switch to root.
ROOTLESS = $(findstring rootless,$(shell docker info --format '{{.SecurityOptions}}' 2>/dev/null))
USER_ID = $(shell id -u):$(shell id -g)
DOCKER_USER = $(if $(ROOTLESS),,--user $(USER_ID))
GOSU = $(if $(ROOTLESS),,gosu $(USER_ID))
# git trusts the checkout without a write to a .gitconfig
GIT_TRUST = -e GIT_CONFIG_COUNT=1 -e GIT_CONFIG_KEY_0=safe.directory -e GIT_CONFIG_VALUE_0=/app

# Installs the PHP tools of ci/ and writes every generated file, in webdevops/php:8.4-alpine
generate:
	docker run --rm $(DOCKER_USER) -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR)":/app -w /app --entrypoint sh webdevops/php:8.4-alpine -c 'composer install --working-dir=ci && ci/console generate'

# Installs the PHP tools of ci/ and checks the PHP code in ci/ with GrumPHP. Only apk runs as root, for the "file"
# command of GrumPHP's BOM check.
check:
	docker run --rm $(GIT_TRUST) -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR)":/app -w /app/ci --entrypoint sh webdevops/php:8.4-alpine -c 'apk add --no-cache file >/dev/null && exec $(GOSU) sh -c "composer install && vendor/bin/grumphp run --no-interaction"'

# Runs only the PHPUnit tests of ci/
test:
	docker run --rm $(DOCKER_USER) -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR)":/app -w /app/ci --entrypoint sh webdevops/php:8.4-alpine -c 'composer install && vendor/bin/phpunit'

# Runs the image tests of IMAGE (webdevops/<image>:<tag>) against a container of REFERENCE, by default IMAGE itself,
# e.g. make test-image IMAGE=webdevops/php-nginx:8.5 REFERENCE=my-build. The tests run in a container of
# ci/image-tests/Dockerfile, which starts the container of the image through the Docker socket. test-image builds that
# runner first; test-image-run uses the runner that is already built, as the CI does after building it with its cache.
DOCKER_SOCKET ?= $(if $(ROOTLESS),$(patsubst unix://%,%,$(shell docker context inspect --format '{{.Endpoints.docker.Host}}' 2>/dev/null)),/var/run/docker.sock)
ifneq ($(filter test-image test-image-run,$(MAKECMDGOALS)),)
ifeq ($(IMAGE),)
$(error Name the image to test, e.g. make test-image IMAGE=webdevops/php-nginx:8.5)
endif
endif

test-image: test-image-runner
	@$(MAKE) --no-print-directory test-image-run

test-image-runner:
	docker build --quiet --tag webdevops-image-tests --file ci/image-tests/Dockerfile . >/dev/null

test-image-run:
	docker run --rm --volume "$(DOCKER_SOCKET)":/var/run/docker.sock webdevops-image-tests $(IMAGE) $(if $(REFERENCE),--reference $(REFERENCE))

structure-test:
	cd tests/structure-test && ./run.sh
