MAKEFLAGS += --silent
.PHONY: generate check test structure-test

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

structure-test:
	cd tests/structure-test && ./run.sh
