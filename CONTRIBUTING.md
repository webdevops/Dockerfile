# Contributing

How the files in this repository are generated, and how the images are built and tested.
How to use the images is described in the [README](README.md) and the [documentation](docs/README.md).

## File generation

The files inside the `docker` directory, the GitHub Actions workflow (`.github/workflows/build.yaml`), the banner at the top of the [README](README.md) and the pages of the images on Docker Hub (`docs/dockerhub/`) are generated with PHP:

```
make generate
```

It installs the PHP tools of `ci/` with Composer and runs `ci/console generate` in `webdevops/php:8.4-alpine`, so you only need `make` and Docker.
The container runs as your user, so the files it writes belong to you.
`ci/console generate` runs these commands in this order; each of them also runs on its own:

| Command | Writes |
| --- | --- |
| `ci/console generate:dockerfile` | `docker/<image>/<tag>/Dockerfile` from the `Dockerfile.twig` next to it and the Twig templates in the `template` directory |
| `ci/console generate:provision` | `docker/<image>/<tag>/conf/` from the `provisioning` directory, as configured in `conf/provision.yml` |
| `ci/console generate:github-ci` | `.github/workflows/build.yaml`, a build and a publish job for every Dockerfile |
| `ci/console generate:readme-banner` | `docs/assets/readme-banner-*.svg` |
| `ci/console generate:dockerhub` | `docs/dockerhub/<repository>.md`, the Docker Hub page of every repository in `conf/dockerhub.yml`, with the tags of the Dockerfiles |

**The generated files are never modified manually.**
Edit the `Dockerfile.twig` templates, the shared templates in `template`, the files in `provisioning` or the settings in `conf` instead.
`template/Dockerfile/layout.twig` writes the header of every Dockerfile and includes its `Dockerfile.twig`.
A `Dockerfile.twig` imports the macros it uses from `template/Dockerfile`, e.g. `{% import 'docker.twig' as docker %}`; a macro imports the macros it calls itself.
`generate:provision` empties the `conf/` directories of an image first, so a file removed from `provisioning` disappears there too.
The commands that the images have in `/usr/local/bin`, such as `apt-install`, `apk-install`, `docker-run-bootstrap` and `generate-locales`, are in `provisioning/baselayout/general/usr/local/bin`. They only handle Debian, Ubuntu and Alpine, the systems the images are built on. `generate:provision` copies them into `docker/toolbox/latest/conf/`, `toolbox` keeps them under `/baselayout`, and `bootstrap` and the PHP images copy them from there with `COPY --from=webdevops/toolbox`.
It checks all of `conf/provision.yml` before it changes a file and stops if a source directory or a tag does not exist, or if an image has another setting than `configuration`.
`generate:dockerfile` stops if a directory `docker/<image>/<tag>` has files but no `Dockerfile.twig`; it first removes the `.DS_Store` files of the macOS Finder under `docker`.
To stop building a tag, remove its directory and those of the tags that build on it; `generate:github-ci` names any it finds.
`generate:dockerhub` checks everything before it writes a page and stops if an image under `docker` has no entry under `built` in `conf/dockerhub.yml`, if an entry under `built` has no Dockerfile any more, if a link points to a file or heading that does not exist, or if a page or a short description is longer than Docker Hub takes (25,000 and 100 bytes). To stop building an image, also move its entry from `built` to `discontinued`; its page then warns that it gets no updates. It removes the pages of repositories that are no longer in `conf/dockerhub.yml`.

`webdevops/php-official` is a deprecated name of `webdevops/php`. `generate:github-ci` publishes a `php` tag under that name too only if `docker: aliases` in `conf/console.yml` lists it; new PHP versions are not added there. When you stop building a `php` tag, remove it from that list as well: `generate:github-ci` stops while the list names a tag without a Dockerfile in `docker/php`, or while a tag directory in `docker/php-official` has a Dockerfile.

## Building in CI

The banner (`docs/assets/readme-banner-*.svg`) takes the PHP versions, web servers, base OS suffixes, Debian releases and platforms from the images that are built.
Its texts are in `conf/readme-banner.yml`. The CI fails if a generated file is out of date or missing.

The PHP code in `ci/` follows the rules of [pluswerk/grumphp-config](https://github.com/andersundsehr/grumphp-config) (PSR-12, PHPStan level 8, Rector, `composer audit`) and has PHPUnit tests in `ci/tests`.
GrumPHP runs all of them; after a change to `ci/`, run it with:

```
make check
```

It installs the PHP tools of `ci/` and runs GrumPHP as your user. `make test` runs only the PHPUnit tests.

The CI job "Check ci/" runs the same checks. No image build waits for it.

Dependabot proposes updates of the PHP packages in `ci/` once a month: one pull request with all minor and patch updates, and one for each new major version.
It does not update the actions in `.github/workflows/build.yaml`, because that file is generated; their versions are in `ci/src/GithubJobBuilder.php`.

GitHub Actions builds every image independently and runs the image tests and structure tests
defined for it before pushing it to Docker Hub. The CI also uses `ghcr.io/webdevops/`, but only
internally; pull the images from Docker Hub.

The job "Update the Docker Hub pages" uploads `docs/dockerhub/<repository>.md` and the short description of each repository to Docker Hub when `build.yaml` runs on `master` after a push or is started by hand. It does not run for pull requests, in the nightly run, in the builds that `php-upstream.yaml` starts (a failed upload would keep it from starting the next one) or in forks, and no other job waits for it.
It logs in with the secrets `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN`. Docker Hub changes a description only for a user with admin rights on the repository, with a password or a personal access token with the scope Read/Write/Delete.

Besides the nightly run, `.github/workflows/php-upstream.yaml` starts `build.yaml` on `master` when the official PHP images get a new PHP release.
Every hour it reads `PHP_VERSION` of the image in the `FROM` line of each `docker/php/<tag>/Dockerfile` and of `webdevops/php:<tag>` and `webdevops/php-nginx:<tag>`.
If one of ours is different or does not exist yet, it starts the build, unless a build of `master` is running or queued (pull requests do not count).
It also does not start one if a build it started in the last 8 hours did not succeed, or if it already started 3 builds in that time; builds started by hand do not count.
An image that cannot be read is skipped with a warning; the next run tries again.
It is written by hand, not generated. Run it by hand with "dry-run" to see which tags are behind without starting a build.

The build steps pass the token of the run (`GITHUB_TOKEN`) to Docker as the build secret `COMPOSER_AUTH`, for PIE (see [PHP extension builds](#php-extension-builds)).
The token may only read the repository; only the jobs that push images to ghcr.io may also write packages there.

## Building and testing locally

Build a single image from its directory. Its parent image (the `FROM` line) and `webdevops/toolbox` are taken from your local images or pulled from Docker Hub:

```
docker build -t webdevops/php-nginx:8.5 docker/php-nginx/8.5
```

Build all PHP images of one version (default `8.5`), in the order they depend on each other:

```
./build-local.sh 8.4
```

Then try an image with the [quick start](README.md#quick-start).

The CI runs the image tests for every image except `toolbox`, and the structure tests for `bootstrap`, `base`, `base-app`, `php`, `php-dev` and `php-nginx`.
To run the image tests against an image you built locally:

```
make test-image IMAGE=webdevops/php-nginx:8.5
```

`REFERENCE` tests another image with the tests and settings of `IMAGE`, e.g. `REFERENCE=my-php-nginx`.
The tests use `/var/run/docker.sock`, in rootless Docker the socket of the current Docker context; set `DOCKER_SOCKET` for another one.

To run the structure tests against the images you built locally:

```
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v $PWD:/app -w /app webdevops/dockerfile-build-env make structure-test
```

It tests the local tags that the CI builds and fails if no such image exists.

## Image tests

The image tests are PHPUnit tests in `ci/image-tests`. They start a container of the image with its own command, so supervisord starts the services as in production, and check it:
files and their owner and mode, commands and their output, PHP extensions and settings, the running services and their ports, and requests through Nginx or Apache to PHP-FPM over http and https.
Most tests of an image run against one container. Its root directory gets the files of `ci/image-tests/Fixtures` first, so `/app` holds the pages that the requests ask for.
The tests of environment variables, commands and hooks start more containers of the image: the environment container, which starts with every variable of `ENVIRONMENT` in `ImageTestCase` set to a value other than its default, containers of their own for variables that would get in the way of other tests, e.g. the process manager of PHP-FPM, which `startTestContainer()` removes after their test, and short runs of one command through the entrypoint, e.g. `root id -un`.
They check what a variable does, not only that its value ends up in a configuration file: a request that hits the memory limit, a cron job that runs at the hour of `TZ`, a connection of Xdebug to a listener.
They request PHP-FPM over FastCGI without a web server, so they also run in `php` and `php-dev`.
They also get the files of `ci/image-tests/Hooks`, scripts in `/entrypoint.d` and in the hook directory of each service that mark that they ran. The first container does not, so that its hook directories are the image's own.
A container counts as started once its entrypoint has run its scripts and started the command, e.g. supervisord.

| Path | Content |
| --- | --- |
| `ci/image-tests/Images/` | One test class per image, e.g. `PhpNginxTest.php` for `webdevops/php-nginx`. An image without a class is not tested. |
| `ci/image-tests/Checks/` | The checks as traits, e.g. `PhpChecks` for every PHP image or `NginxChecks` for the images with Nginx. A test class uses the traits of its image. |
| `ci/image-tests/ImageTestCase.php` | Starts and removes the containers, the variables of the environment container, and the helpers of the checks, e.g. `assertRootScript()`, `assertServiceRunning()`, `request()`, `phpFpmBody()` and `runContainer()` |
| `ci/image-tests/Fixtures/` | Files for all containers: the pages in `/app`, and in `/opt/image-tests` small programs that stand in for what a test talks to: `fastcgi-request.php` sends a request straight to PHP-FPM, `smtp-sink.php` takes the mail of Postfix in place of a mail catcher, `smtp-relay.php` asks Postfix to relay, `dbgp-listener.php` takes the connections of Xdebug in place of an IDE, `capture-mail.sh` stands in for sendmail, and `nginx.conf` is a configuration for `SERVICE_NGINX_OPTS` |
| `ci/image-tests/Hooks/` | Files for the further containers: the hooks, a command `image-test` of its own, a second virtual host for the test of `WEB_ALIAS_DOMAIN`, and an Apache configuration for the test of `SERVICE_APACHE_OPTS` |

`ci/console test:image webdevops/<image>:<tag>` runs the tests of an image; `webdevops/php-official`, a deprecated name of `webdevops/php`, has the tests of `webdevops/php`. It takes the distribution and its version from `dockerTest` in `conf/console.yml`, and removes the containers of the image afterwards, also when the run is interrupted with Ctrl-C or `docker stop`.
A runner killed with `SIGKILL` leaves its containers behind; they carry the label `webdevops.image-test`, so `docker rm --force $(docker ps --all --quiet --filter label=webdevops.image-test)` removes them.
It runs in a container of `ci/image-tests/Dockerfile`, which has PHP and the `docker` command and gets the Docker socket: `make test-image` builds that image (`make test-image-runner`) and runs the tests in it (`make test-image-run`).
The runner builds on the official PHP image, not on a webdevops image, so a broken `webdevops/php` cannot break the tests that find the defect.
In the CI, the job `image-test-runner` builds it once per architecture into the cache of GitHub Actions; the image jobs wait for it, load it from the cache and run `make test-image-run`. If that job fails, the image jobs still run and build the runner themselves.

## PHP extension builds

PHP images require Docker BuildKit 0.16 or later. The official `ghcr.io/php/pie:bin` image
supplies PIE through a build-only mount; the existing INI configuration still
controls extension loading. PIE requests remain unpinned, and compilation uses
`nproc` parallel jobs.

PIE resolves and downloads the extensions through the GitHub API. The `RUN` instructions with PIE mount the build
secret `COMPOSER_AUTH` as an environment variable, which Composer, and with it PIE, reads. Without the secret, PIE
sends its requests without a token, and GitHub limits them per IP address. To build with your token:

```
export COMPOSER_AUTH='{"github-oauth":{"github.com":"<token>"}}'
docker build --secret id=COMPOSER_AUTH,env=COMPOSER_AUTH -t webdevops/php:8.5 docker/php/8.5
```

`./build-local.sh` passes the secret on when `COMPOSER_AUTH` is set.

Protobuf, Vips and XML-RPC are built directly from their official upstream sources.
The existing XML-RPC **1.0.0RC3** pin is preserved on all supported PHP versions.
Protobuf on PHP 8.2–8.5 uses the latest upstream release. If a future release raises its minimum PHP
version, older PHP image builds can fail; there is no automatic fallback to an
older release. The existing CI matrix checks compatibility across PHP versions.

The native Vips extension is deprecated and no longer maintained upstream. It is
retained for existing applications, using the upstream default branch (currently
`master`): GitHub tags predate the PECL 1.0.13 release and would downgrade the
extension. This includes changes beyond the last PECL release.
No extension installation needs `pecl.php.net`; the image tests check that PHP and
PHP-FPM load every extension.
