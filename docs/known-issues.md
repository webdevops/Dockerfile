# Known issues

These options and tools are part of the images but do not work, or work only in some images. The rest of the documentation leaves them out. Each entry says what happens, which images are affected and what to do instead. Where a GitHub issue already exists, it is linked.

## Problems in the images

### `SERVICE_SUPERVISOR_USER`

- **Effect:** Any value other than `root` makes the container exit with code 2 right after the start: supervisord switches to that user and then cannot create its control socket `/.supervisor.sock` (`Cannot open an HTTP server: … EACCES`).
- **Images:** all images except `bootstrap` and `toolbox`.
- **Instead:** Leave it unset. PHP-FPM runs its workers as `application` anyway.

### `LOG_STDOUT` and `LOG_STDERR` for PHP-FPM

- **Effect:** PHP-FPM writes its access log and its own messages to the stderr of the container, not to `/docker.stdout` and `/docker.stderr`, so `LOG_STDOUT` and `LOG_STDERR` do not apply to them. The `docker.conf` of the official PHP image sets `error_log` and `access.log` to `/proc/self/fd/2` after the configuration of the image. The errors of PHP scripts go to `/docker.stderr`, but the workers of PHP-FPM run as `application` and can only write to a file that they may open. In `php` and `php-dev`, they create the file of `LOG_STDERR` and write the errors there. In the images with Nginx or Apache, the web server creates that file as root first, so the errors go to the stderr of the container, as they do in every image without `LOG_STDERR`.
- **Images:** `php`, `php-dev`, `php-nginx`, `php-nginx-dev`, `php-apache` and `php-apache-dev`, Debian and Alpine.
- **Instead:** Read them with `docker compose logs`. To have PHP-FPM's messages and the PHP errors in the file of `LOG_STDERR` in every image, also set `fpm.global.error_log: /docker.stderr`. `php.error_log` gives the PHP errors of PHP-FPM and the CLI a file of their own, in a directory that `application` may write to, for example `/app`. The access log needs a pool file that sorts after `docker.conf`, see [`fpm.pool.<key>`](#fpmpoolkey-for-the-directives-of-dockerconf).

### `docker-cronjob` on Alpine

- **Effect:** Fails. `/etc/cron.d` does not exist, and BusyBox `crond` does not read it.
- **Images:** all Alpine images.
- **Instead:** Add the job to `/etc/crontabs/root`, without the user column. In images that do not start cron by default, also run `docker-service enable cron`.

  ```dockerfile
  RUN echo '*/5 * * * * /usr/local/bin/php /app/bin/console app:cleanup' >> /etc/crontabs/root
  ```
- **Issue:** related question [#280](https://github.com/webdevops/Dockerfile/issues/280).

### `fpm.pool.<key>` for the directives of `docker.conf`

- **Effect:** No effect for `listen`, `access.log`, `clear_env`, `catch_workers_output` and `decorate_workers_output`. The official PHP image sets them in the pool file `docker.conf`, which PHP-FPM reads after `application.conf`, so its values win. Other directives work, for example `fpm.pool.pm.max_requests`.
- **Images:** `php-*`, Debian and Alpine.
- **Instead:** Add a pool file whose name sorts after `docker.conf`, for example `zz-pool.conf` with a `[www]` section, to `/opt/docker/etc/php/fpm/pool.d/`.

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
