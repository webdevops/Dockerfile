# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<ALIAS_DOMAIN>"   "$WEB_ALIAS_DOMAIN"
find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<SERVERNAME>"     "$HOSTNAME"
