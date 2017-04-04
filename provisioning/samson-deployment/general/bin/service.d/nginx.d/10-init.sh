# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
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
