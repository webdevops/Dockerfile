if [[ ! -e "$WEB_DOCUMENT_ROOT" ]]; then
    echo ""
    echo "[WARNING] WEB_DOCUMENT_ROOT does not exists with path \"$WEB_DOCUMENT_ROOT\"!"
    echo ""
fi

# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
go-replace \
    -s "<DOCUMENT_INDEX>" \
    -r "$WEB_DOCUMENT_INDEX" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

go-replace \
    -s "<DOCUMENT_ROOT>" \
    -r "$WEB_DOCUMENT_ROOT" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

go-replace \
    -s "<ALIAS_DOMAIN>" \
    -r "$WEB_ALIAS_DOMAIN" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

go-replace \
    -s "<SERVERNAME>" \
    -r "$HOSTNAME" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

if [[ -n "${WEB_PHP_SOCKET+x}" ]]; then
    ## WEB_PHP_SOCKET is set
    go-replace \
        -s "<PHP_SOCKET>" \
        -r "$WEB_PHP_SOCKET" \
        --path=/opt/docker/etc/nginx/ \
        --path-pattern='*.conf' \
        --ignore-empty
else
    ## WEB_PHP_SOCKET is not set, remove PHP files
    rm -f -- /opt/docker/etc/nginx/conf.d/10-php.conf
    rm -f -- /opt/docker/etc/nginx/vhost.common.d/10-php.conf
fi
