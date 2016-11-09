# Mail sandbox container layout

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

Mail sandbox which catches all mails and delivers them to a local user and is accessable via IMAP and via Web (roundcube)

## Environment variables

Variable             | Description
-------------------- | ------------------------------------------------------------------------------
`MAILBOX_USERNAME`   | Username for mailbox (Default `dev`)
`MAILBOX_PASSWORD`   | Password for mailbox (Default `dev`)

## Container info

Image                               | Info                                                                       
----------------------------------- | ----------------------------------------------------------------------------------
webdevops/mail-sandbox:latest       | [![](https://badge.imagelayers.io/webdevops/mail-sandbox:latest.svg)](https://imagelayers.io/?images=webdevops/mail-sandbox:latest 'Get your own badge on imagelayers.io')
