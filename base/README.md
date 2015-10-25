# Base container layout

## Containers

* [Centos 7](centos-7/Dockerfile)
* [Ubuntu 12.04 "Precise Pangolin" LTS](ubuntu-12.04/Dockerfile)
* [Ubuntu 14.04 "Trusty Tahr" LTS](ubuntu-14.04/Dockerfile)
* [Ubuntu 15.04 "Vivid Vervet"](ubuntu-15.04/Dockerfile)
* [Ubuntu 15.10 "Wily Werewolf"](ubuntu-15.10/Dockerfile)

## Environment variables

Variable            | Description
------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`        | Predefined CLI script for service
`APPLICATION_UID`   | Application UID (Effective user ID)
`APPLICATION_GID`   | Application GID (Effective group ID)

## Filesystem layout

The whole docker directroy is deployed into `/opt/docker/`.


Directory                       | Description
------------------------------- | ------------------------------------------------------------------------------
`/opt/docker/bin`               | Script directory for various script eg. `entrypoint.sh`
`/opt/docker/bin/bootstrap.d`   | Directory for bash `*.sh` scripts which will automatcally run by `bootstrrap.sh` (will be removed after run, for usage in `Dockerfile`)
`/opt/docker/bin/entrypoint.d`  | Directory for bash `*.sh` scripts which will automatcally run by `entrypoint.sh`
`/opt/docker/bin/service.d`     | Service (wrapper) scripts for supervisord
<br>                            |
`/opt/docker/etc`               | Configuration directory
`/opt/docker/etc/supervisor.d`  | Supervisor service configuration `*.conf` directory
<br>                            |
`/opt/docker/provision`         | Ansible provisioning configuration directory

 

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

## `entrypoint.sh`

CMD             | Description
--------------- | ------------------------------------------------------------------------------
supervisord     | Start supervisor and configured services
noop            | Endless noop loop (endless sleep)
root            | Root shell (external usage)
cli             | Run predefined `CLI_SCRIPT` (env variable) as `EFFECTIVE_USER` if defined
all other       | Run defined command as `EFFECTIVE_USER` if defined
