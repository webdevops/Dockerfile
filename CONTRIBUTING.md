# Contributing

How the files in this repository are generated, and how the images are built and tested.
How to use the images is described in the [README](README.md) and the [documentation](docs/README.md).

## File generation

The files inside the `docker` directory, the GitHub Actions workflow (`.github/workflows/build.yaml`) and the banner at the top of the [README](README.md) are generated with PHP:

```
make generate
```

It installs the PHP tools of `ci/` with Composer and runs `ci/console generate` in `webdevops/php:8.4-alpine`, so you only need `make` and Docker.
The container runs as your user, so the files it writes belong to you.
`ci/console generate` runs these commands in this order; each of them also runs on its own:

| Command | Writes |
| --- | --- |
| `ci/console generate:dockerfile` | `docker/<image>/<tag>/Dockerfile` from the `Dockerfile.twig` next to it and the Twig templates in the `template` directory, and the links `docker/php/<version>` to `docker/php-official/<version>` |
| `ci/console generate:provision` | `docker/<image>/<tag>/conf/` from the `provisioning` directory, as configured in `conf/provision.yml` |
| `ci/console generate:github-ci` | `.github/workflows/build.yaml`, a build and a publish job for every Dockerfile |
| `ci/console generate:readme-banner` | `docs/assets/readme-banner-*.svg` |

**The generated files are never modified manually.**
Edit the `Dockerfile.twig` templates, the shared templates in `template`, the files in `provisioning` or the settings in `conf` instead.
`template/Dockerfile/layout.twig` writes the header of every Dockerfile and includes its `Dockerfile.twig`.
A `Dockerfile.twig` imports the macros it uses from `template/Dockerfile`, e.g. `{% import 'docker.twig' as docker %}`; a macro imports the macros it calls itself.
`generate:provision` empties the `conf/` directories of an image first, so a file removed from `provisioning` disappears there too.
It checks all of `conf/provision.yml` before it changes a file and stops if a source directory or a tag does not exist, or if an image has another setting than `configuration`.
`generate:dockerfile` stops if a directory `docker/<image>/<tag>` has files but no `Dockerfile.twig`; it first removes the `.DS_Store` files of the macOS Finder under `docker`.
To stop building a tag, remove its directory and those of the tags that build on it; `generate:github-ci` names any it finds. `generate:dockerfile` adds and removes the links in `docker/php` along with the `php-official` tags.

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

The CI runs the image tests for every image except `toolbox` and `dockerfile-build-env`, and the structure tests for `bootstrap`, `base`, `base-app`, `php`, `php-dev` and `php-nginx`.
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
Most tests of an image run against one container. Its root directory gets the files of `ci/image-tests/Fixtures` first, so `/app` holds the pages that the requests ask for, and hooks in `/entrypoint.d` and in the hook directory of each service mark that they ran.
The tests of environment variables and commands start more containers of the image with the same files: the environment container, which starts with every variable of `ENVIRONMENT` in `ImageTestCase` set to a value other than its default, and short runs of one command through the entrypoint, e.g. `root id -un`.

| Path | Content |
| --- | --- |
| `ci/image-tests/Images/` | One test class per image, e.g. `PhpNginxTest.php` for `webdevops/php-nginx`. An image without a class is not tested. |
| `ci/image-tests/Checks/` | The checks as traits, e.g. `PhpChecks` for every PHP image or `NginxChecks` for the images with Nginx. A test class uses the traits of its image. |
| `ci/image-tests/ImageTestCase.php` | Starts and removes the containers, the variables of the environment container, and the helpers of the checks, e.g. `assertRootScript()`, `assertServiceRunning()`, `request()` and `runContainer()` |
| `ci/image-tests/Fixtures/` | Files for the containers: the pages in `/app`, the hooks, a command `image-test` of its own, and `smtp-sink.php`, a small SMTP server in place of a mail catcher for the mail tests |

`ci/console test:image webdevops/<image>:<tag>` runs the tests of an image; `webdevops/php-official` has the tests of `webdevops/php`. It takes the distribution and its version from `dockerTest` in `conf/console.yml`, and removes the containers of the image afterwards, also when the run is interrupted with Ctrl-C or `docker stop`.
A runner killed with `SIGKILL` leaves its containers behind; they carry the label `webdevops.image-test`, so `docker rm --force $(docker ps --all --quiet --filter label=webdevops.image-test)` removes them.
It runs in a container of `ci/image-tests/Dockerfile`, which has PHP and the `docker` command and gets the Docker socket: `make test-image` builds that image (`make test-image-runner`) and runs the tests in it (`make test-image-run`).
The CI builds the image with the cache of GitHub Actions and then runs `make test-image-run`.

## PHP extension builds

PHP images require Docker BuildKit. The official `ghcr.io/php/pie:bin` image
supplies PIE through a build-only mount; the existing INI configuration still
controls extension loading. PIE requests remain unpinned, and compilation uses
`nproc` parallel jobs.

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
