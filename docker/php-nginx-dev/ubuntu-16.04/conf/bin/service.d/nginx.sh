#!/usr/bin/env bash
set -e

if [[ ! -e "$WEB_DOCUMENT_ROOT" ]]; then
    echo ""
    echo "[WARNING] WEB_DOCUMENT_ROOT does not exists with path \"$WEB_DOCUMENT_ROOT\"!"
    echo ""
fi

# Replace markers
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<DOCUMENT_INDEX>" "$WEB_DOCUMENT_INDEX"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<DOCUMENT_ROOT>"  "$WEB_DOCUMENT_ROOT"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<ALIAS_DOMAIN>"   "$WEB_ALIAS_DOMAIN"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<SERVERNAME>"     "$HOSTNAME"

exec /usr/sbin/nginx
