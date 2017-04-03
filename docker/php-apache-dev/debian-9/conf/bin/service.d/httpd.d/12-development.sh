find /opt/docker/etc/httpd/ -iname '*.conf' -print0 | xargs -0 -r go-replace "<WEB_NO_CACHE_PATTERN>" -r "$WEB_NO_CACHE_PATTERN" -- > /dev/null
