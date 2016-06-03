find /opt/docker/etc/httpd/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet "<WEB_NO_CACHE_PATTERN>" "$WEB_NO_CACHE_PATTERN"
