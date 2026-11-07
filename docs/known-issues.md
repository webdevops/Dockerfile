# Known issues

These options and tools are part of the images but do not work, or work only in some images. The rest of the documentation leaves them out. Each entry says what happens, which images are affected, what to do instead and where the problem is tracked.

## Problems in the images

### `docker-php-setting`

- **Effect:** Always prints its usage text and exits with code 1, also for a valid call such as `docker-php-setting memory_limit 512M`. The setting is not changed.
- **Images:** `php-*`, Debian and Alpine.
- **Instead:** Set `php.<key>` or a shortcut such as `PHP_MEMORY_LIMIT`, see [PHP](environment-variables.md#php), or [replace `php.ini`](customization.md#php).
- **Issue:** none yet.

### `FTP_PATH`

- **Effect:** No effect. Uploads always go to the FTP user's home directory, `/home/<FTP_USER>`.
- **Images:** `vsftp`.
- **Instead:** Mount your volume at `/home/<FTP_USER>`, see [vsftp](images/vsftp.md).
- **Issue:** none yet.

### `SERVICE_POSTFIX_OPTS`

- **Effect:** No effect. The value is passed to `postfix start`, which ignores it.
- **Images:** all images with Postfix: `base`, `base-app`, `nginx`, `apache`, `vsftp`, `php-*`.
- **Instead:** Change the Postfix configuration with `postconf -e` in a [service hook](customization.md#service-hooks) in `/opt/docker/bin/service.d/postfix.d/`, or use `POSTFIX_RELAYHOST` and `POSTFIX_MYNETWORKS`.
- **Issue:** none yet.

### `SERVICE_SUPERVISOR_USER`

- **Effect:** Any value other than `root` makes the container exit with code 2 right after the start: supervisord switches to that user and then cannot create its control socket `/.supervisor.sock` (`Cannot open an HTTP server: … EACCES`).
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Leave it unset. PHP-FPM runs its workers as `application` anyway.
- **Issue:** none yet.

### `/opt/docker/provision/onbuild.d`

- **Effect:** Scripts in this directory are never run. Only the removed Ansible provisioning used it.
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Use `bootstrap.d` or `build.d` with `docker-run-bootstrap`, see [Scripts at image build](customization.md#scripts-at-image-build).
- **Issue:** none yet.

### Ansible provisioning (`docker-provision`, `docker-ansible-install`)

- **Effect:** Both commands fail. They need Python 2 and Python 2 packages that no longer exist.
- **Images:** all images except `bootstrap`, `storage` and `toolbox`.
- **Instead:** Shell scripts in `bootstrap.d` or `build.d`, see [Scripts at image build](customization.md#scripts-at-image-build).
- **Issue:** none yet.

### Blackfire agent

- **Effect:** With `PHP_DEBUGGER=blackfire`, supervisord starts the `blackfire-agent` service, but the agent is not installed. It exits with code 127 and is restarted over and over, which fills the container log. The Blackfire PHP extension itself is loaded.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Run the agent in its own container and point the extension at it with `php.blackfire.agent_socket`, for example `tcp://blackfire:8307`. The restart loop in the log remains.
- **Issue:** [#529](https://github.com/webdevops/Dockerfile/issues/529) (closed, still reproducible).

### `cli` command without `APPLICATION_USER`

- **Effect:** Fails with `APPLICATION_USER: unbound variable`.
- **Images:** `base`, `nginx`, `apache`, `vsftp`. It works in `base-app` and `php-*`.
- **Instead:** Set `APPLICATION_USER`, for example to `root`, or run the command directly; any other command runs as `root`, see [Commands](customization.md#commands).
- **Issue:** none yet.

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
- **Issue:** none yet.

### Nginx without IPv6

- **Effect:** If the container has no IPv6, Nginx cannot listen on `[::]:80` and `[::]:443`, and supervisord restarts it over and over. PHP-FPM is not affected.
- **Images:** `php-nginx`, `php-nginx-dev`, `nginx`.
- **Instead:** Remove the IPv6 `listen` lines in a [start script](customization.md#scripts-at-container-start), for example `/entrypoint.d/10-no-ipv6.sh`:

  ```sh
  for f in $(grep -rl 'listen \[::\]' /opt/docker/etc/nginx); do sed -i '/listen \[::\]/d' "$f"; done
  ```
- **Issue:** none yet.

### Xdebug settings written into `php.ini`

- **Effect:** When an `XDEBUG_*` variable is set, the development images append its setting to `/opt/docker/etc/php/php.ini`, your own file. A read-write mount gets one more line on every start of the container, also on the host; with a `:ro` mount the container exits at start.
- **Images:** `php-dev`, `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Set the Xdebug directives in your `php.ini` or with `php.xdebug.<key>` instead of the `XDEBUG_*` variables. Or bring your `php.ini` in with `COPY`; then only the copy inside the container is changed.
- **Issue:** none yet.

### Several wildcards in `VIRTUAL_HOST`

- **Effect:** Only the first `*.` entry becomes a wildcard for dnsmasq; further wildcard entries are dropped.
- **Images:** all images with dnsmasq.
- **Instead:** Use one wildcard entry.
- **Issue:** none yet.

### Expired TLS certificate

- **Effect:** The self-signed certificate for `*.vm` on port 443 expired on 2025-05-01.
- **Images:** `php-nginx`, `php-nginx-dev`, `php-apache`, `php-apache-dev`, `nginx`, `apache`.
- **Instead:** Mount your own `server.crt` and `server.key`, see [Configuration files](customization.md#configuration-files).
- **Issue:** none yet.

### Alpine 3.11

- **Effect:** The `alpine` tags of `bootstrap`, `base`, `base-app`, `nginx` and `apache` are built on Alpine 3.11, which has been out of support since November 2021, with Nginx 1.16.1 and Apache 2.4.51.
- **Images:** `bootstrap:alpine`, `base:alpine`, `base-app:alpine`, `nginx:alpine`, `apache:alpine`. The Alpine variants of the PHP images are not affected; they use the Alpine release of the official PHP image.
- **Instead:** Use the `ubuntu-22.04` tags, or a PHP image.
- **Issue:** [#453](https://github.com/webdevops/Dockerfile/issues/453) (closed, still applies).

### SSH in `php-nginx-dev` and `php-apache-dev`

- **Effect:** The SSH server runs, but the accounts have no password, so nobody can log in. Only `php-dev` sets the password `dev`.
- **Images:** `php-nginx-dev`, `php-apache-dev`.
- **Instead:** Use `docker compose exec`.
- **Issue:** none yet.

### Composer 1 on PHP 8.4 and 8.5

- **Effect:** With `COMPOSER_VERSION=1`, every Composer run prints deprecation notices.
- **Images:** `php-*` with PHP 8.4 and 8.5.
- **Instead:** Use Composer 2, the default.
- **Issue:** none yet.

## Build tools

- **Effect:** The Makefile targets that call `bin/console` (`provision`, `build`, `push`, `test`, `graph`) need Python 2. On a host without Python 2 they fail with `python2: No such file or directory`. Inside `webdevops/dockerfile-build-env`, `make provision` and `make graph` work, but `make test` fails as well.
- **Instead:** Run `make provision` in `webdevops/dockerfile-build-env` as shown in the [README](../README.md#file-generation). Build images with `docker build` or `./build-local.sh`, see [Building and testing locally](../README.md#building-and-testing-locally). The CI runs the serverspec and structure tests for every image.
- **Issue:** [#549](https://github.com/webdevops/Dockerfile/issues/549) (move away from Python 2).

## Migration from older images

- `XDEBUG_REMOTE_*`, `XDEBUG_IDE_KEY`, `XDEBUG_MAX_NESTING_LEVEL` and `XDEBUG_PROFILER_*` are ignored; they belong to Xdebug 2. Set the Xdebug 3 directive with `php.xdebug.<key>` instead, for example `php.xdebug.max_nesting_level: "500"` or `php.xdebug.idekey: PHPSTORM`. The variables listed under [Development images](environment-variables.md#development-images) still work.
- `SERVICE_BLACKFIRE_AGENT_OPTS` has no effect, because the agent is not installed (see [Blackfire agent](#blackfire-agent)).
- Tags that are no longer built stay on Docker Hub without updates: PHP 5.6 to 8.1, all operating system tags such as `debian-9` or `ubuntu-18.04`, and the `latest` and `alpine` tags of the PHP images, which were last built in April 2020. Always use a version tag such as `8.5` or `8.5-alpine`.
