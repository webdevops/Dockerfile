# Base container layout

## Containers
Container                           | Distribution name                                                                 
----------------------------------- | -------------------------
`webdevops/base:ubuntu-12.04`       | precise                   
`webdevops/base:ubuntu-14.04`       | trusty (LTS)             
`webdevops/base:ubuntu-15.04`       | vivid                    
`webdevops/base:ubuntu-15.10`       | wily                     
`webdevops/base:debian-7`           | wheezy                   
`webdevops/base:debian-8`           | jessie                   
`webdevops/base:centos-7`           |                          

## Environment variables

Variable            | Description
------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`        | Predefined CLI script for service
`APPLICATION_UID`   | Application UID (Effective user ID)
`APPLICATION_GID`   | Application GID (Effective group ID)

## Filesystem layout

The whole docker directroy is deployed into `/opt/docker/`.


Directory                            | Description
------------------------------------ | ------------------------------------------------------------------------------
`/opt/docker/bin`                    | Script directory for various script eg. `entrypoint.sh`
`/opt/docker/bin/entrypoint.d`       | Entrypoint scripts
`/opt/docker/bin/service.d`          | Service (wrapper) scripts for supervisord
<br>                                 |
`/opt/docker/etc`                    | Configuration directory
`/opt/docker/etc/supervisor.d`       | Supervisor service configuration `*.conf` directory
<br>                                 |
`/opt/docker/provision`              | Ansible provisioning configuration directory
`/opt/docker/provision/roles`        | Ansible roles configuration directory
`/opt/docker/provision/bootstrap.d`  | Directory for bash `*.sh` scripts which will automatcally run by `bootstrrap.sh` (will be removed after run, for usage in `Dockerfile`)
`/opt/docker/provision/entrypoint.d` | Directory for bash `*.sh` scripts which will automatcally run by `entrypoint.sh`
`/opt/docker/provision/onbuild.d`    | Directory for bash `*.sh` scripts which will automatcally run by `onbuild` (`bootstrap.sh onbuild` must be called for execution with ONBUILD RUN) 
 

File                                         | Description
-------------------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/bin/config.sh`                  | Config for `entrypoint.sh` and other scripts (eg. `/opt/docker/bin/entrypoint.d`). All bash functions/variables can be used in custom scripts.
`/opt/docker/bin/entrypoint.sh`              | Main entrypoint for docker container
`/opt/docker/bin/logwatch.sh`                | Log reader for childen processes (can be used with named pipes)
`/opt/docker/bin/provision.sh`               | Ansible provision wrapper script
`/opt/docker/bin/control.sh`                 | Control script for container and provisioning registration handling
<br>                                         |
`/opt/docker/etc/supervisor.conf`            | Main supervisor configuration (will include other scripts in `/opt/docker/etc/supervisor.d/*.conf`)
`/opt/docker/etc/supervisor.d/cron.conf`     | Cron service script _(disabled by default)_
`/opt/docker/etc/supervisor.d/ssh.conf`      | SSH server service script _(disabled by default)_


## Ansible provisioning

Whole configuration will deployed in `/opt/docker/provision`.

Available tags:
- bootstrap (only run once)
- entrypoint (run at startup)

If there is no `playbook.yml` it will be created dynamically based on registred roles by `control.sh`.
`bootstrap` roles will only run once (at docker build) and not again on inherited containers.
`entrypoint` roles will run at each startup also on inherited containers.

To use the modular ansible provisioning you have to deploy your own role into `/opt/docker/provision/roles`, eg.:

Directory: `/opt/docker/provision/roles/yourrolename/`
Main task file: `/opt/docker/provision/roles/yourrolename/tasks/main.yml`

To register your role execute following script in your `Dockerfile`:

For `bootstrap` and `entrypoint` tag:
`RUN bash /opt/docker/bin/control.sh provision.role.bootstrap yourrolename`

For only `bootstrap` tag:
`RUN bash /opt/docker/bin/control.sh provision.role.bootstrap.bootstrap yourrolename`

For only `entrypoint` tag:
`RUN bash /opt/docker/bin/control.sh provision.role.bootstrap.entrypoint yourrolename`

## `entrypoint.sh`

CMD             | Description
--------------- | ------------------------------------------------------------------------------
supervisord     | Start supervisor and configured services
noop            | Endless noop loop (endless sleep)
root            | Root shell (external usage)
cli             | Run predefined `CLI_SCRIPT` (env variable) as `EFFECTIVE_USER` if defined
all other       | Run defined command as `EFFECTIVE_USER` if defined

## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/base:latest               | [![](https://badge.imagelayers.io/webdevops/base:latest.svg)](https://imagelayers.io/?images=webdevops/base:latest 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-12.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-12.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-12.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-14.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-14.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-14.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-15.04         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.04.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.04 'Get your own badge on imagelayers.io')
webdevops/base:ubuntu-15.10         | [![](https://badge.imagelayers.io/webdevops/base:ubuntu-15.10.svg)](https://imagelayers.io/?images=webdevops/base:ubuntu-15.14 'Get your own badge on imagelayers.io')
webdevops/base:centos-7             | [![](https://badge.imagelayers.io/webdevops/base:centos-7.svg)](https://imagelayers.io/?images=webdevops/base:centos-7 'Get your own badge on imagelayers.io')
webdevops/base:debian-7             | [![](https://badge.imagelayers.io/webdevops/base:debian-7.svg)](https://imagelayers.io/?images=webdevops/base:debian-7 'Get your own badge on imagelayers.io')
webdevops/base:debian-8             | [![](https://badge.imagelayers.io/webdevops/base:debian-8.svg)](https://imagelayers.io/?images=webdevops/base:debian-8 'Get your own badge on imagelayers.io')
