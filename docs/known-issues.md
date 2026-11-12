# Known issues

These options and tools are part of the images but do not work, or work only in some images. The rest of the documentation leaves them out. Each entry says what happens, which images are affected and what to do instead. Where a GitHub issue already exists, it is linked.

## Problems in the images

### `docker-php-setting`

- **Effect:** Always prints its usage text and exits with code 1, also for a valid call such as `docker-php-setting memory_limit 512M`. The setting is not changed.
- **Images:** `php-*`, Debian and Alpine.
- **Instead:** Set `php.<key>` or a shortcut such as `PHP_MEMORY_LIMIT`, see [PHP](environment-variables.md#php), or [replace `php.ini`](customization.md#php).

### `SERVICE_POSTFIX_OPTS`

- **Effect:** No effect. The value is passed to `postfix start`, which ignores it.
- **Images:** all images with Postfix: `base`, `base-app`, `nginx`, `apache`, `php-*`.
- **Instead:** Change the Postfix configuration with `postconf -e` in a [service hook](customization.md#service-hooks) in `/opt/docker/bin/service.d/postfix.d/`, or use `POSTFIX_RELAYHOST` and `POSTFIX_MYNETWORKS`.

### `SERVICE_SUPERVISOR_USER`

- **Effect:** Any value other than `root` makes the container exit with code 2 right after the start: supervisord switches to that user and then cannot create its control socket `/.supervisor.sock` (`Cannot open an HTTP server: … EACCES`).
- **Images:** all images except `bootstrap` and `toolbox`.
- **Instead:** Leave it unset. PHP-FPM runs its workers as `application` anyway.

### Blackfire agent

- **Effect:** With `PHP_DEBUGGER=blackfire`, supervisord starts the `blackfire-agent` service, but the agent is not installed. It exits with code 127 and is restarted over and over, which fills the container log. The Blackfire PHP extension itself is loaded.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Run the agent in its own container and point the extension at it with `php.blackfire.agent_socket`, for example `tcp://blackfire:8307`. The restart loop in the log remains.
- **Issue:** [#529](https://github.com/webdevops/Dockerfile/issues/529) (closed, still reproducible).

### `cli` command without `APPLICATION_USER`

- **Effect:** Fails with `APPLICATION_USER: unbound variable`.
- **Images:** `base`, `nginx`, `apache`. It works in `base-app` and `php-*`.
- **Instead:** Set `APPLICATION_USER`, for example to `root`, or run the command directly; any other command runs as `root`, see [Commands](customization.md#commands).

### `docker-cronjob` on Alpine

- **Effect:** Fails. `/etc/cron.d` does not exist, and BusyBox `crond` does not read it.
- **Images:** all Alpine images.
- **Instead:** Add the job to `/etc/crontabs/root`, without the user column. In images that do not start cron by default, also run `docker-service enable cron`.

  ```dockerfile
  RUN echo '*/5 * * * * /usr/local/bin/php /app/bin/console app:cleanup' >> /etc/crontabs/root
  ```
- **Issue:** related question [#280](https://github.com/webdevops/Dockerfile/issues/280).

### `docker-image-cleanup` in the Alpine PHP images

- **Effect:** Exits with code 1 (`find: /var/lib/apk/: No such file or directory`), so `RUN apk-install … && docker-image-cleanup` fails the build.
- **Images:** the `-alpine` tags of `php-*`. It works in `base:alpine`.
- **Instead:** Leave it out; `apk-install` keeps no package cache.

### Alpine 3.20 repository in the Alpine PHP images

- **Effect:** `/etc/apk/repositories` also lists the community repository of Alpine 3.20 (`https://dl-4.alpinelinux.org/alpine/v3.20/community/`), although the images are based on a newer Alpine release, for example 3.24 in `8.5-alpine`. `apk` reads that index too, so a package that is missing from the current release can be installed from Alpine 3.20, built against older libraries.
- **Images:** `php-*` with the Alpine tags `8.2-alpine` to `8.5-alpine`.
- **Instead:** Remove the line in your Dockerfile before you install packages: `RUN sed -i '/\/v3\.20\//d' /etc/apk/repositories`.

### Package sources in the Debian Nginx images

- **Effect:** `/etc/apt/sources.list` holds only the nginx.org repository and ends without a line break. A line appended with `echo "deb ..." >> /etc/apt/sources.list` is joined to the last line: apt ignores the new repository and only prints warnings such as `component misspelt in sources.list`. The nginx.org key is stored in the deprecated global keyring `/etc/apt/trusted.gpg`, which apt trusts for every repository; `apt-get update` warns about it.
- **Images:** `php-nginx` and `php-nginx-dev` with the Debian tags `8.2` to `8.5`.
- **Instead:** Add your own repositories as a new file in `/etc/apt/sources.list.d/`, for example `echo "deb http://deb.debian.org/debian bookworm-backports main" > /etc/apt/sources.list.d/backports.list`.

### Xdebug settings written into `php.ini`

- **Effect:** When an `XDEBUG_*` variable is set, the development images append its setting to `/opt/docker/etc/php/php.ini`, your own file. A read-write mount gets one more line on every start of the container, also on the host; with a `:ro` mount the container exits at start.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Set the Xdebug directives in your `php.ini` or with `php.xdebug.<key>` instead of the `XDEBUG_*` variables. Or bring your `php.ini` in with `COPY`; then only the copy inside the container is changed.

### `fpm.pool.<key>` for the directives of `docker.conf`

- **Effect:** No effect for `listen`, `access.log`, `clear_env`, `catch_workers_output` and `decorate_workers_output`. The official PHP image sets them in the pool file `docker.conf`, which PHP-FPM reads after `application.conf`, so its values win. Other directives work, for example `fpm.pool.pm.max_requests`.
- **Images:** `php-*`, Debian and Alpine.
- **Instead:** Add a pool file whose name sorts after `docker.conf`, for example `zz-pool.conf` with a `[www]` section, to `/opt/docker/etc/php/fpm/pool.d/`.

### Several wildcards in `VIRTUAL_HOST`

- **Effect:** Only the first `*.` entry becomes a wildcard for dnsmasq; further wildcard entries are dropped.
- **Images:** all images with dnsmasq.
- **Instead:** Use one wildcard entry.

### Expired TLS certificate

- **Effect:** The self-signed certificate for `*.vm` on port 443 expired on 2025-05-01.
- **Images:** `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`, `nginx`, `apache`.
- **Instead:** Mount your own `server.crt` and `server.key`, see [Configuration files](customization.md#configuration-files).

### Alpine 3.11

- **Effect:** The `alpine` tags of `bootstrap`, `base`, `base-app`, `nginx` and `apache` are built on Alpine 3.11, which has been out of support since November 2021, with Nginx 1.16.1 and Apache 2.4.51.
- **Images:** `bootstrap:alpine`, `base:alpine`, `base-app:alpine`, `nginx:alpine`, `apache:alpine`. The Alpine variants of the PHP images are not affected; they use the Alpine release of the official PHP image.
- **Instead:** Use the `ubuntu-22.04` tags, or a PHP image.
- **Issue:** [#453](https://github.com/webdevops/Dockerfile/issues/453) (closed, still applies).

### `latest` and `alpine` tags of the PHP images

- **Effect:** The CI no longer builds these tags, but they are still on Docker Hub. An image used without a tag, for example `image: webdevops/php-nginx`, is the `latest` image from April 2020 and gets no updates. The `alpine` tags are from April 2020 as well, those of the development images from April 2019.
- **Images:** `php`, `php-dev`, `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`.
- **Instead:** Always use a version tag such as `8.5` or `8.5-alpine`.

### SSH in `php-nginx-dev` and `php-apache-dev`

- **Effect:** The SSH server runs, but the accounts have no password, so nobody can log in. Only `php-dev` sets the password `dev`.
- **Images:** `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Use `docker compose exec`.

### Composer 1 on PHP 8.4 and 8.5

- **Effect:** With `COMPOSER_VERSION=1`, every Composer run prints deprecation notices.
- **Images:** `php-*` with PHP 8.4 and 8.5.
- **Instead:** Use Composer 2, the default.

## Migration from older images

- `XDEBUG_REMOTE_*`, `XDEBUG_IDE_KEY`, `XDEBUG_MAX_NESTING_LEVEL` and `XDEBUG_PROFILER_*` are ignored; they belong to Xdebug 2. Set the Xdebug 3 directive with `php.xdebug.<key>` instead, for example `php.xdebug.max_nesting_level: "500"` or `php.xdebug.idekey: PHPSTORM`. The variables listed under [Development images](environment-variables.md#development-images) still work.
- `SERVICE_BLACKFIRE_AGENT_OPTS` has no effect, because the agent is not installed (see [Blackfire agent](#blackfire-agent)).
- Tags that are no longer built stay on Docker Hub without updates: PHP 5.6 to 8.1 and all operating system tags such as `debian-9` or `ubuntu-18.04`. For `latest` and `alpine`, see [above](#latest-and-alpine-tags-of-the-php-images).
- The images `webdevops/storage`, `webdevops/vsftp` and `webdevops/ssh` are no longer built. Their last images stay on Docker Hub without updates (`ssh` since December 2022). For replacements, see [Replacing discontinued images](discontinued-images.md).
- The Ansible provisioning is removed: `docker-provision`, `docker-ansible-install`, `/opt/docker/bin/provision` and the `provision.role*` commands of `/opt/docker/bin/control.sh`. They needed Python 2 and Ansible packages that no longer exist and did not work in any image. `/opt/docker/provision/onbuild.d` is removed too; scripts in it were never run. Use shell scripts in `bootstrap.d` or `build.d`, see [Scripts at image build](customization.md#scripts-at-image-build).
