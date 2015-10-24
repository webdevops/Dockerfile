# PHP container layout

## Containers

* [Centos 7 - PHP 5.4](centos-7/Dockerfile)
* [Ubuntu 12.04 "Precise Pangolin" LTS - PHP 5.3](ubuntu-12.04/Dockerfile)
* [Ubuntu 14.04 "Trusty Tahr" LTS - PHP 5.5](ubuntu-14.04/Dockerfile)
* [Ubuntu 15.04 "Vivid Vervet" - PHP 5.6](ubuntu-15.04/Dockerfile)
* [Ubuntu 15.10 "Wily Werewolf" - PHP 5.6](ubuntu-15.10/Dockerfile)

## Environment variables

Variable            | Description
------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`        | Predefined CLI script for service
`APPLICATION_UID`   | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`   | PHP-FPM GID (Effective group ID)
