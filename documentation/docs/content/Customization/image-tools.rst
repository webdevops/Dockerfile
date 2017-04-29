==================
Docker image tools
==================

docker-service
--------------

For enabling or disabling services run `docker-service enable` or `docker-service disable` inside your Dockerfile::

    RUN docker-service enable ssh

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

For adding cronjobs the `docker-cronjob` script can be used in your Dockerfile::

    RUN docker-cronjob '* * * * * application /app/cron.php`

Because this comand is run in shell mode make sure you add appropriate quotes to disable wildcard matching.

docker-php-setting
------------------

Only available on php images!

This scripts sets php.ini setting globaly::

    RUN docker-php-setting memory_limit 1G

    RUN docker-php-setting --raw error_reporting 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED'

docker-provision
----------------

The `docker-provision` script crontols the ansible provision system. See provision for more details.

(Will be replaced in future)

go-replace
----------

Simple but powerfull search&replace and template processing tool for manipulating files inside Docker::

    # normal search&replace
    go-replace -s VIRTUAL_HOST -r "$VIRTUAL_HOST" daemon.conf

    # or with template
    go-replace --mode=template daemon.conf.tmpl:daemon.conf


For more informations see documentation inside `go-replace repository <https://github.com/webdevops/go-replace>`_.
