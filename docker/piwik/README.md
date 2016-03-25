# Piwik container

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

Based on `webdevops/php-nginx:ubuntu-14.04` with automatic Piwik installer

Install location is `/app/piwik`, crontask is automatically configured.

## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`PIWIK_URL`            | URL of piwik installation (requried for crontask)

## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/piwik:latest              | [![](https://badge.imagelayers.io/webdevops/piwik:latest.svg)](https://imagelayers.io/?images=webdevops/piwik:latest 'Get your own badge on imagelayers.io')
