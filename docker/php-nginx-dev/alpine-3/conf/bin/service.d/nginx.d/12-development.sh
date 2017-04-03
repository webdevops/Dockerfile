find /opt/docker/etc/nginx/ -iname '*.conf' -print0 | xargs -0 -r go-replace -s "<WEB_NO_CACHE_PATTERN>" -r "$WEB_NO_CACHE_PATTERN"
