============================
Supervisor Daemon (Services)
============================

.. important:: Supervisor is only available in Docker images which are based on ``webdevops/base``!

Introduction
------------

Supervisor daemon is used to start and supervise more than one process in Docker containers. More about supervisor can
be found on supervisor homepage at http://supervisord.org/

Enable and disable services
---------------------------

For enabling services run `docker-service-enable` or `docker-service-disable` inside your Dockerfile.

eg::

    RUN docker-service-enable ssh

This task will also trigger an auto installation if the daemon is not installed.

Configuration
-------------

The main supervisor configuration file is located at ``/opt/docker/etc/supervisor.conf`` and only controls the
supervisor daemon itself. All services are configured inside ``/opt/docker/etc/supervisor.d/`` directory.

Example configuration for hhvm::

    [group:hhvm]
    programs=hhvmd
    priority=20

    [program:hhvmd]
    command = /opt/docker/bin/service.d/hhvm.sh
    process_name=%(program_name)s
    directory = /var/run/hhvm/
    startsecs = 0
    autostart = true
    autorestart = true
    stdout_logfile=/dev/stdout
    stdout_logfile_maxbytes=0
    stderr_logfile=/dev/stderr
    stderr_logfile_maxbytes=0

Service daemon scripts
----------------------

For every service there is a small bash script inside ``/opt/docker/bin/service.d/`` which takes care how to start the
service. This script also have a modular task runner which runs files from ``/opt/docker/bin/service.d/SERVICE.d/*.sh``.

Example for HHVM:

    HHVM's service script is located at ``/opt/docker/bin/service.d/hhvm.sh``.

    Before running HHVM all scripts found with ``/opt/docker/bin/service.d/hhvm.d/*.sh`` will be executed.
