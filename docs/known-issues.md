# Known issues

These options and tools are part of the images but do not work, or work only in some images. The rest of the documentation leaves them out. Each entry says what happens, which images are affected and what to do instead. Where a GitHub issue already exists, it is linked.

## Problems in the images

### `docker-php-setting`

- **Effect:** Always prints its usage text and exits with code 1, also for a valid call such as `docker-php-setting memory_limit 512M`. The setting is not changed.
- **Images:** `php-*`, Debian and Alpine.
- **Instead:** Set `php.<key>` or a shortcut such as `PHP_MEMORY_LIMIT`, see [PHP](environment-variables.md#php), or [replace `php.ini`](customization.md#php).

### `FTP_PATH`

- **Effect:** No effect. Uploads always go to the FTP user's home directory, `/home/<FTP_USER>`.
- **Images:** `vsftp`.
- **Instead:** Mount your volume at `/home/<FTP_USER>`, see [vsftp](images/vsftp.md).

### `SERVICE_POSTFIX_OPTS`

- **Effect:** No effect. The value is passed to `postfix start`, which ignores it.
- **Images:** all images with Postfix: `base`, `base-app`, `nginx`, `apache`, `vsftp`, `php-*`.
- **Instead:** Change the Postfix configuration with `postconf -e` in a [service hook](customization.md#service-hooks) in `/opt/docker/bin/service.d/postfix.d/`, or use `POSTFIX_RELAYHOST` and `POSTFIX_MYNETWORKS`.

### `SERVICE_SUPERVISOR_USER`

- **Effect:** Any value other than `root` makes the container exit with code 2 right after the start: supervisord switches to that user and then cannot create its control socket `/.supervisor.sock` (`Cannot open an HTTP server: … EACCES`).
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Leave it unset. PHP-FPM runs its workers as `application` anyway.

### `/opt/docker/provision/onbuild.d`

- **Effect:** Scripts in this directory are never run. Only the removed Ansible provisioning used it.
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Use `bootstrap.d` or `build.d` with `docker-run-bootstrap`, see [Scripts at image build](customization.md#scripts-at-image-build).

### Ansible provisioning (`docker-provision`, `docker-ansible-install`)

- **Effect:** Both commands fail. They need Python 2 and Python 2 packages that no longer exist.
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Shell scripts in `bootstrap.d` or `build.d`, see [Scripts at image build](customization.md#scripts-at-image-build).

### Blackfire agent

- **Effect:** With `PHP_DEBUGGER=blackfire`, supervisord starts the `blackfire-agent` service, but the agent is not installed. It exits with code 127 and is restarted over and over, which fills the container log. The Blackfire PHP extension itself is loaded.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Run the agent in its own container and point the extension at it with `php.blackfire.agent_socket`, for example `tcp://blackfire:8307`. The restart loop in the log remains.
- **Issue:** [#529](https://github.com/webdevops/Dockerfile/issues/529) (closed, still reproducible).

### `cli` command without `APPLICATION_USER`

- **Effect:** Fails with `APPLICATION_USER: unbound variable`.
- **Images:** `base`, `nginx`, `apache`, `vsftp`. It works in `base-app` and `php-*`.
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

### Nginx and PHP-FPM without IPv6

- **Effect:** If the container has no IPv6, Nginx cannot listen on `[::]:80` and `[::]:443`, and PHP-FPM cannot listen on `[::]:9000` (`Address family not supported by protocol`). supervisord restarts them over and over.
- **Images:** Nginx in `php-nginx`, `php-nginx-dev` and `nginx`. PHP-FPM in `php`, `php-dev`, `php-nginx-dev` and `php-apache-dev`; in `php-nginx` and `php-apache` it listens on `127.0.0.1:9000` and is not affected.
- **Instead:** Change the listen addresses in a [start script](customization.md#scripts-at-container-start), for example `/entrypoint.d/10-no-ipv6.sh`:

  ```sh
  for f in $(grep -rlE 'listen \[::\]|listen = \[::\]' /opt/docker/etc/nginx /opt/docker/etc/php/fpm 2>/dev/null); do
    sed -i -e '/^\s*listen \[::\]/d' -e 's/^listen = \[::\]:9000/listen = 0.0.0.0:9000/' "$f"
  done
  true
  ```

### Xdebug settings written into `php.ini`

- **Effect:** When an `XDEBUG_*` variable is set, the development images append its setting to `/opt/docker/etc/php/php.ini`, your own file. A read-write mount gets one more line on every start of the container, also on the host; with a `:ro` mount the container exits at start.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Set the Xdebug directives in your `php.ini` or with `php.xdebug.<key>` instead of the `XDEBUG_*` variables. Or bring your `php.ini` in with `COPY`; then only the copy inside the container is changed.

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

### Images on ghcr.io

- **Effect:** The CI pushes every image to Docker Hub and to `ghcr.io/webdevops/<image>`. Pulling from ghcr.io without a login fails with `unauthorized`, so ghcr.io is no alternative when Docker Hub limits your pulls.
- **Images:** all.
- **Instead:** Pull from Docker Hub (`webdevops/<image>`). To avoid Docker Hub's limits, log in to Docker Hub or use a pull-through cache.

### SSH in `php-nginx-dev` and `php-apache-dev`

- **Effect:** The SSH server runs, but the accounts have no password, so nobody can log in. Only `php-dev` sets the password `dev`.
- **Images:** `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Use `docker compose exec`.

### Composer 1 on PHP 8.4 and 8.5

- **Effect:** With `COMPOSER_VERSION=1`, every Composer run prints deprecation notices.
- **Images:** `php-*` with PHP 8.4 and 8.5.
- **Instead:** Use Composer 2, the default.

## Build tools

- **Effect:** The Makefile targets that call `bin/console` (`provision`, `build`, `push`, `test`, `graph`) need Python 2. On a host without Python 2 they fail with `python2: No such file or directory`. Inside `webdevops/dockerfile-build-env`, `make provision` and `make graph` work, but `make test` fails as well.
- **Instead:** Run `make provision` in `webdevops/dockerfile-build-env` as shown in the [README](../README.md#file-generation). Build images with `docker build` or `./build-local.sh`, see [Building and testing locally](../README.md#building-and-testing-locally). The CI runs the serverspec and structure tests for every image.
- **Issue:** [#549](https://github.com/webdevops/Dockerfile/issues/549) (move away from Python 2).

## Migration from older images

- `XDEBUG_REMOTE_*`, `XDEBUG_IDE_KEY`, `XDEBUG_MAX_NESTING_LEVEL` and `XDEBUG_PROFILER_*` are ignored; they belong to Xdebug 2. Set the Xdebug 3 directive with `php.xdebug.<key>` instead, for example `php.xdebug.max_nesting_level: "500"` or `php.xdebug.idekey: PHPSTORM`. The variables listed under [Development images](environment-variables.md#development-images) still work.
- `SERVICE_BLACKFIRE_AGENT_OPTS` has no effect, because the agent is not installed (see [Blackfire agent](#blackfire-agent)).
- Tags that are no longer built stay on Docker Hub without updates: PHP 5.6 to 8.1 and all operating system tags such as `debian-9` or `ubuntu-18.04`. For `latest` and `alpine`, see [above](#latest-and-alpine-tags-of-the-php-images).
