---
description: Short overview about what makes WebDevOps images special
---

# Docker images

## WebDevOps image features

### Logging

To be written

### Configuration

To be written

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

