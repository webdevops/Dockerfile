# WebDevOps Dockerfiles

Dockerfiles for various prebuilt docker containers


Dockerfile                  | Description                                                                        | Depends on                                                           |
--------------------------- | ---------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
[`base`](base/README.md)    | Base containers for WebDevOps service containers                                   | official docker files                                                |
[`php`](php/README.md)      | PHP (cli and fpm) service containers                                               | [`webdevops/base`](https://hub.docker.com/r/webdevops/base/)         |
[`vsftp`](vsftp/README.md)  | VSFTP (ftp service) service container                                              | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`storage`](storage/README.md) | Storage (noop) container                                                           | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`ssh`](ssh/README.md)      | SSH service container                                                              | [`webdevops/base:latest`](https://hub.docker.com/r/webdevops/base/)  |
[`samson-deployment`](samson-deployment/README.md) | [Samson](https://github.com/webdevops/samson-deployment) based deployment service  | [`zendesk/samson`](https://hub.docker.com/r/zendesk/samson/)         |
