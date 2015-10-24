#!/usr/bin/env bash

HTTPD_CONF="/opt/docker/etc/httpd/vhost.conf"

replaceTextInFile "<DOCUMENT_INDEX>" "${HTTPD_DOCUMENT_INDEX}" "$HTTPD_CONF"
replaceTextInFile "<DOCUMENT_ROOT>"  "${HTTPD_DOCUMENT_ROOT}"  "$HTTPD_CONF"
replaceTextInFile "<ALIAS_DOMAIN>"   "${HTTPD_ALIAS_DOMAIN}"   "$HTTPD_CONF"

#echo "ServerName $(hostname)" >> /etc/httpd/conf.d/servername.conf