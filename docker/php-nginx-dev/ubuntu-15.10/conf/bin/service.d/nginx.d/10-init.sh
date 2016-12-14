if [[ ! -e "$WEB_DOCUMENT_ROOT" ]]; then
    echo ""
    echo "[WARNING] WEB_DOCUMENT_ROOT does not exists with path \"$WEB_DOCUMENT_ROOT\"!"
    echo ""
fi

# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<DOCUMENT_INDEX>" "$WEB_DOCUMENT_INDEX"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<DOCUMENT_ROOT>"  "$WEB_DOCUMENT_ROOT"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<ALIAS_DOMAIN>"   "$WEB_ALIAS_DOMAIN"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<SERVERNAME>"     "$HOSTNAME"

if [[ -n "${WEB_PHP_SOCKET+x}" ]]; then
    ## WEB_PHP_SOCKET is set
    find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<PHP_SOCKET>" "$WEB_PHP_SOCKET"
else
    ## WEB_PHP_SOCKET is not set, remove PHP files
    rm -f -- /opt/docker/etc/nginx/conf.d/10-php.conf
    rm -f -- /opt/docker/etc/nginx/vhost.common.d/10-php.conf
fi
