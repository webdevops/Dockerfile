go-replace \
    -s "<WEB_NO_CACHE_PATTERN>" \
    -r "$WEB_NO_CACHE_PATTERN" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

