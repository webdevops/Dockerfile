---
description: Short overview about what makes WebDevOps images special
---

# Docker images

## WebDevOps image features

We're trying to solve the most common problems that everybody stumbles upon while building complex and production-ready docker images.

### Multiple services

We use `supervisord` to run multiple [services](using-the-images/services.md) \(unix processes\) in the background. A real web application needs more than just a webserver. E.g. a cron daemon to run scheduled processes, postfix to send mails, etc.

### Logging

Docker is designed to run only a single process in the foreground and use the console output as logs. You can then [redirect the output](https://docs.docker.com/config/containers/logging/configure/#supported-logging-drivers) of docker containers to gelf, fluentd, splunk or something else.

But when running multiple processes, you must redirect all outputs to stderr/stdout of those processes. We configured all our services not to write container internal files but to output everything on stdout/stderr.

{% hint style="info" %}
To be written: Chapter about syslog-ng
{% endhint %}

### Configuration

It's best practice of building Docker images to use ENV variables to control the configuration of the service that is running in a container. We try to provide all common needed configuration options which can by quite tricky if you think about PHP with all the fpm parameters, ini parameters, etc. 

We try to keep you from building your own Docker images as good as possible and not to mount config files or similar. 

### Provisioning

To be written

### Testing

To be written

### Dockerfile templating

DRY, etc.

## Image hierarchy

To write: Example dependency tree of php-nginx-dev with explanation of distro tagging

## Basic images

As our images are very well encapsulated depending on the use case, we have 3 levels of base images:

1. Bootstrap
2. Base
3. Base-App

### Bootstrap images

The bootstrap images e.g. `webdevops/bootstrap:debian-9` are the first layer of our images extending the official images like e.g.  debian, ubuntu or php with our [baselayout](https://github.com/webdevops/Docker-Image-Baselayout). 

In this first layer we add some useful scripts like \(just an excerpt\):

* `apt-install`
* `go-replace`
* `gosu`

This simplifies the usage of the package managers for each linux distribution. They are optimized to be used in a Docker environment. They update, install silently and cleanup afterwards.

The bootstrap images ensure that the foreign but official images are up-to-date as we don't trust that the basic packages are properly updated on the basic builds of the official images.

See the [bootstrap image documentation](basic-images/bootstrap.md) for more details.

### Base images

The base images e.g. `webdevops/base:debian-9` can be considered as the first layer of "real" WebDevOps images. They extend the bootstrap images and add our layer of provisioning scripts together with the supervisor daemon to use multiple service processes inside an image.

From this point on you can use our custom [service commands](), [services](using-the-images/services.md) and [scripts](using-the-images/scripts.md).

See the [base image documentation](basic-images/base.md) for more details.

### Base-App images

The base-app images e.g. webdevops/base-app:debian-9 extend the base images. They contain some common needed tools like git, zip, rsync, open-ssh and the [application user mechanism](using-the-images/application-user-mechanism.md).

See the [base-app image documentation](basic-images/base-app.md) for more details.

