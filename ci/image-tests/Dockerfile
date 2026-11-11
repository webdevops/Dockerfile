# Runs the image tests: ci/console test:image starts a container of the image with the docker command and checks it.
# Build it from the repository root, which .dockerignore limits to ci/ and conf/:
#   docker build --tag webdevops/image-tests --file ci/image-tests/Dockerfile .
# and run it with the Docker socket:
#   docker run --rm --volume /var/run/docker.sock:/var/run/docker.sock webdevops/image-tests webdevops/php-nginx:8.5
FROM webdevops/php:8.4-alpine

RUN apk add --no-cache docker-cli

WORKDIR /app
COPY ci/composer.json ci/composer.lock ci/
RUN composer install --working-dir=ci --no-interaction --no-progress --no-plugins --no-scripts
COPY conf/ conf/
COPY ci/ ci/
RUN composer dump-autoload --working-dir=ci --no-interaction --no-plugins --no-scripts

ENTRYPOINT ["ci/console", "test:image"]
