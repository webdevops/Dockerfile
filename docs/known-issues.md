# Known issues

These options and tools are part of the images but do not work, or work only in some images. The rest of the documentation leaves them out. Each entry says what happens, which images are affected and what to do instead. Where a GitHub issue already exists, it is linked.

## Problems in the images

### `SERVICE_SUPERVISOR_USER`

- **Effect:** Any value other than `root` makes the container exit with code 2 right after the start: supervisord switches to that user and then cannot create its control socket `/.supervisor.sock` (`Cannot open an HTTP server: … EACCES`).
- **Images:** all images except `bootstrap` and `toolbox`.
- **Instead:** Leave it unset. PHP-FPM runs its workers as `application` anyway.

### `latest` and `alpine` tags of the PHP images

- **Effect:** The CI no longer builds these tags, but they are still on Docker Hub. An image used without a tag, for example `image: webdevops/php-nginx`, is the `latest` image from April 2020 and gets no updates. The `alpine` tags are from April 2020 as well, those of the development images from April 2019.
- **Images:** `php`, `php-dev`, `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`.
- **Instead:** Always use a version tag such as `8.5` or `8.5-alpine`.

### `apt-add-repository` in the PHP 8.6 images

- **Effect:** Fails with `E: Unable to locate package software-properties-common`: the command installs `software-properties-common` first, which Debian 13 no longer has.
- **Images:** `php-*` with the Debian tag `8.6`.
- **Instead:** Write the repository to a file in `/etc/apt/sources.list.d/`, for example `RUN echo "deb http://deb.debian.org/debian trixie-backports main" > /etc/apt/sources.list.d/backports.list`.

### Composer 1 on PHP 8.4 to 8.6

- **Effect:** With `COMPOSER_VERSION=1`, every Composer run prints deprecation notices. From PHP 8.5 on, Composer 1 also cannot download packages: commands such as `composer require` stop with `Uncaught TypeError: stream_context_create(): Argument #1 ($options) must be an array with valid callbacks as values`.
- **Images:** `php-*` with PHP 8.4 (deprecation notices), 8.5 and 8.6 (no downloads).
- **Instead:** Use Composer 2, the default.

## Migration from older images

- `XDEBUG_REMOTE_*`, `XDEBUG_IDE_KEY`, `XDEBUG_MAX_NESTING_LEVEL` and `XDEBUG_PROFILER_*` are ignored; they belong to Xdebug 2. Set the Xdebug 3 directive with `php.xdebug.<key>` instead, for example `php.xdebug.max_nesting_level: "500"` or `php.xdebug.idekey: PHPSTORM`. The variables listed under [Development images](environment-variables.md#development-images) still work.
- Tags that are no longer built stay on Docker Hub without updates: PHP 5.6 to 8.1 and all operating system tags such as `debian-9` or `ubuntu-18.04`. For `latest` and `alpine`, see [above](#latest-and-alpine-tags-of-the-php-images).
- The images `webdevops/nginx` and `webdevops/apache` and the `alpine` tags of `webdevops/bootstrap`, `webdevops/base` and `webdevops/base-app` are no longer built. Their last images, on Alpine 3.24, stay on Docker Hub without updates, and so do the older tags of `nginx` and `apache`, among them `latest` (Ubuntu 18.04 from April 2020). For replacements, see [Replacing discontinued images](discontinued-images.md).
- The images `webdevops/storage`, `webdevops/vsftp` and `webdevops/ssh` are no longer built. Their last images stay on Docker Hub without updates (`ssh` since December 2022). For replacements, see [Replacing discontinued images](discontinued-images.md).
- The Ansible provisioning is removed: `docker-provision`, `docker-ansible-install`, `/opt/docker/bin/provision` and the `provision.role*` commands of `/opt/docker/bin/control.sh`. They needed Python 2 and Ansible packages that no longer exist and did not work in any image. `/opt/docker/provision/onbuild.d` is removed too; scripts in it were never run. Use shell scripts in `bootstrap.d` or `build.d`, see [Scripts at image build](customization.md#scripts-at-image-build).
