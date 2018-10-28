# How to contribute?

## Dockerfile templating

To be written \(Jinja2, generation etc.\)

## Provisioning

To be written \(shell scripts, service scripts, etc.\)

## Building

Local building of containers can be done with `make` and `Makefile`:

| Command | Description |
| :--- | :--- |
| `sudo make setup` | To Install dependancies of build chain tools |
| `make all` | Build all containers _fast mode_ \(parallel building, `FAST=1`\) |
| `FAST=0 make all` | Build all containers _slow mode_ \(serial building\) |
| `DEBUG=1 make all` | Show log of build process even if process is successfull |
| `FORCE=1 make all` | Force container build \(`docker build --no-cache ...`\) |
| `WHITELIST="alpine-3 centos-7" make all` | Build all container with tag alpine-3 or centos-7 |
|  |  |
| `make baselayout` | Build and deploy baselayout.tar |
| `make provision` | Deploy all configuration files from [\_provisioning/](https://github.com/webdevops/Dockerfile/tree/511a870fa90fe53da5c63a95b4254f6980e6d3d2/_provisioning/README.md) |
| `make dist-update` | Update local distrubtion images \(CentOS, Debian, Ubuntu\) |
| `make full` | Run provision and build all images |
|  |  |
| `make test` | Run testsuite \(use currently available docker images on your docker host\) |
| `make test-hub-images` | Run testsuite but pull newest docker images from docker hub first |
|  |  |
| `make push` | Run tests and rebuild them \(use cache\) and push them to docker hub |
| `make publish` | Run `dist-update`, `all` with FORCE and `push` |
|  |  |
| `make base` | Build all base containers |
| `make service` | Build all service containers |
| `make php` | Build all php containers |
| `make hhvm` | Build all hhvm containers |
| `make nginx` | Build all nginx containers |
| `make apache` | Build all apache containers |
| `make webdevops/php-nginx` | Build specific containers \(as example\) |



## Tests

### ServerSpec

To be written

### Infra

To be written

### Container structure tests

To be written

