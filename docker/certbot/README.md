# Certbot container layout

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`CERTBOT_EMAIL`        | Email of sysadmin
`CERTBOT_DOMAIN`       | Registered dns or public ip

## USAGE

To create or renew existing certificate
```bash
docker run -ti --rm \
           -v /etc/letsencrypt:/etc/letsencrypt \
           -v /your/document_root:/var/www \
        webdevops/certbot /usr/bin/certbot certonly \
	    --agree-tos \
	    --webroot \
	    -w /var/www
	    -d webdevops.io \
	    -m "webmaster@webdevops.io"
```
See [commandline options](https://certbot.eff.org/docs/using.html#command-line-options)

## Template a cronjob to reissue the certificate

Create a file **/etc/cron.monthly/reissue**
```bash
#!/bin/sh
set -euo pipefail
# Certificate reissue

docker run -ti --rm \
           -v /etc/letsencrypt:/etc/letsencrypt \
           -v /your/document_root:/var/www \
       webdevops/certbot /usr/bin/certbot renew

 ```
make file executable : chmod +x /etc/cron.monthly/reissue

see [Renewal](https://certbot.eff.org/docs/using.html#renewal)