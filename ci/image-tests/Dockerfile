# Runs the image tests: ci/console test:image starts a container of the image with the docker command and checks it.
# Build it from the repository root, which .dockerignore limits to ci/ and conf/:
#   docker build --tag webdevops/image-tests --file ci/image-tests/Dockerfile .
# and run it with the Docker socket:
#   docker run --rm --volume /var/run/docker.sock:/var/run/docker.sock webdevops/image-tests webdevops/php-nginx:8.5
#
# It builds on the official PHP image, not on a webdevops image: a broken webdevops/php must not break the tests that
# find the defect.
FROM php:8.4-cli-alpine

# docker for the containers of the tests, pcntl for Ctrl-C and docker stop, unzip for Composer
RUN apk add --no-cache docker-cli unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pcntl \
    && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY ci/composer.json ci/composer.lock ci/
RUN composer install --working-dir=ci --no-interaction --no-progress --no-plugins --no-scripts
COPY conf/ conf/
COPY ci/ ci/
RUN composer dump-autoload --working-dir=ci --no-interaction --no-plugins --no-scripts

ENTRYPOINT ["ci/console", "test:image"]
