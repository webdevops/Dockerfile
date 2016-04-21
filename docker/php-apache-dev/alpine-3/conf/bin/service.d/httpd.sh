#!/usr/bin/env bash
set -e

if [[ ! -e "$WEB_DOCUMENT_ROOT" ]]; then
    echo ""
    echo "[WARNING] WEB_DOCUMENT_ROOT does not exists with path \"$WEB_DOCUMENT_ROOT\"!"
    echo ""
fi

# Apache gets grumpy about PID files pre-existing
rm -f /var/run/httpd/httpd.pid

rpl --quiet "<DOCUMENT_INDEX>" "$WEB_DOCUMENT_INDEX" /opt/docker/etc/httpd/*.conf
rpl --quiet "<DOCUMENT_ROOT>"  "$WEB_DOCUMENT_ROOT"  /opt/docker/etc/httpd/*.conf
rpl --quiet "<ALIAS_DOMAIN>"   "$WEB_ALIAS_DOMAIN"   /opt/docker/etc/httpd/*.conf
rpl --quiet "<SERVERNAME>"     "$HOSTNAME"           /opt/docker/etc/httpd/*.conf

exec /usr/sbin/apachectl -DFOREGROUND
