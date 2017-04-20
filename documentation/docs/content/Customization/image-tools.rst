==================
Docker image tools
==================

docker-service
--------------

For enabling or disabling services run `docker-service-enable` or `docker-service-disable` inside your Dockerfile.

eg::

    RUN docker-service-enable ssh

This task will also trigger an auto installation if the daemon is not installed.

Available services are specified inside `/opt/docker/etc/supervisor.d/`.

Common services are:

- cron
- dnsmasq
- postfix
- ssh
- syslog

docker-cronjob
--------------

For adding cronjobs the `docker-cronjob` script can be used in your Dockerfile.

eg::

    RUN docker-cronjob '* * * * * application /app/cron.php`

Because this comand is run in shell mode make sure you add appropriate quotes to disable wildcard matching.

docker-provision
----------------

The `docker-provision` script crontols the ansible provision system. See provision for more details.

(Will be replaced in future)
