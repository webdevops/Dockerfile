# Services

## What are services

As a service we understand a background daemon process that should be running inside the container. 

Services available in our images are:

* `cron`
* `dnsmasq`
* `postfix`
* `ssh`
* `supervisor`
* `syslog-ng`
* `nginx`
* `php-fpm`
* `etc.`

## Supervisord

The `supervisord` services is the only service which  is an exception because it's the default service that is started by the default entrypoint and command. 

[Supervisor](http://supervisord.org/configuration.html) is a python based process control system which is easy to use and configure. We make use of `supervisord` to redirect the output of the background processes to stdout or stderr which are collected by the [docker logging](https://docs.docker.com/config/containers/logging/configure/) mechanism. 

Usually it's quite complicated to collect service logs as they write into a logging directory which would usually be something `/var/log/cron.log`. Most users would build a log collector to get the logs out of the containers, but if we redirect the output, there's no need for that.

You can also use the `supervisorctl` inside the container to restart services:

[![asciicast](https://asciinema.org/a/209106.png)](https://asciinema.org/a/209106)

## Service components

Services consist of 3 parts:

* A run-file located under `/opt/docker/bin/service.d/${SERVICE}.sh`
* A supervisord config-file located under `/opt/docker/etc/supervisor.d/${SERVICE}.conf`
* A provision-file located under `/opt/docker/provision/service.d/${SERVICE}.sh`

{% hint style="info" %}
The services are usually added in the base layer of our images
{% endhint %}

## Enabling services

You can control which services should be available by using the `docker-service` commands:

```text
RUN docker-service enable cron
```

This command will first execute the provisioning script and enable the service by modifying the supervisor config file \(set `autostart=true`, default is `false`\).

