# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r go-replace -s "<ALIAS_DOMAIN>" -r "$WEB_ALIAS_DOMAIN"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r go-replace -s "<SERVERNAME>"   -r "$HOSTNAME"
