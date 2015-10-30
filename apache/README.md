# PHP container layout

## Containers

* [Centos 7 - PHP 5.4](centos-7/Dockerfile)
* [Ubuntu 14.04 "Trusty Tahr" LTS - PHP 5.5](ubuntu-14.04/Dockerfile)

## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`CLI_SCRIPT`           | Predefined CLI script for service
`APPLICATION_UID`      | PHP-FPM UID (Effective user ID)
`APPLICATION_GID`      | PHP-FPM GID (Effective group ID)
`HTTPD_DOCUMENT_ROOT`  | Document root for Apache HTTPD
`HTTPD_DOCUMENT_INDEX` | Document index (eg. `index.php`) for Apache HTTPD
`HTTPD_ALIAS_DOMAIN`   | Alias domains (eg. `*.vm`) for Apache HTTPD
