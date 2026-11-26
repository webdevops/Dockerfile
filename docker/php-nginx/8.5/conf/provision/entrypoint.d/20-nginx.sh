# Create tmp dir for nginx
mkdir -p /var/tmp/nginx/

# Prevent startup of nginx (ubuntu 16.04 needs it)
ln -f -s /var/lib/nginx/logs /var/log/nginx

# Replace markers
go-replace \
    -s "<DOCUMENT_INDEX>" -r "$WEB_DOCUMENT_INDEX" \
    -s "<DOCUMENT_ROOT>" -r "$WEB_DOCUMENT_ROOT" \
    -s "<ALIAS_DOMAIN>" -r "$WEB_ALIAS_DOMAIN" \
    -s "<SERVERNAME>" -r "$HOSTNAME" \
    -s "<PHP_SOCKET>" -r "$WEB_PHP_SOCKET" \
    -s "<PHP_TIMEOUT>" -r "$WEB_PHP_TIMEOUT" \
    -s "<SERVICE_NGINX_CLIENT_MAX_BODY_SIZE>" -r "$SERVICE_NGINX_CLIENT_MAX_BODY_SIZE" \
    --path=/opt/docker/etc/nginx/ \
    --path-pattern='*.conf' \
    --ignore-empty

if [[ -z "$WEB_PHP_SOCKET" ]]; then
    ## WEB_PHP_SOCKET is not set, remove PHP files
    rm -f -- /opt/docker/etc/nginx/conf.d/10-php.conf
    rm -f -- /opt/docker/etc/nginx/vhost.common.d/10-php.conf
fi

# Without IPv6 in the container nginx cannot listen on [::]: delete the IPv6 listen
# directives that vhost.conf includes, so nginx listens on IPv4 only
if [[ ! -e /proc/net/if_inet6 ]]; then
    rm -f -- /opt/docker/etc/nginx/vhost.ipv6/*.conf || true
fi

# SERVICE_NGINX_ACCESS_LOG and SERVICE_NGINX_ERROR_LOG: the access log and the error log of Nginx, which the image sends
# to /docker.stdout and /docker.stderr, go to these files instead, for example /dev/null to turn a log off. Only the
# lines that still point to /docker.stdout or /docker.stderr change, so a log of your own stays where you put it.
if [[ -n "${SERVICE_NGINX_ACCESS_LOG:-}" ]]; then
    for NGINX_CONF_PATH in /opt/docker/etc/nginx/ /etc/nginx/; do
        go-replace --mode=line --regex --regex-backrefs \
            -s '^([ \t]*access_log[ \t]+)/docker\.stdout([ \t;].*)$' -r "\${1}${SERVICE_NGINX_ACCESS_LOG}\${2}" \
            --path="$NGINX_CONF_PATH" --path-pattern='*.conf'
    done
fi

if [[ -n "${SERVICE_NGINX_ERROR_LOG:-}" ]]; then
    for NGINX_CONF_PATH in /opt/docker/etc/nginx/ /etc/nginx/; do
        go-replace --mode=line --regex --regex-backrefs \
            -s '^([ \t]*error_log[ \t]+)/docker\.stderr([ \t;].*)$' -r "\${1}${SERVICE_NGINX_ERROR_LOG}\${2}" \
            --path="$NGINX_CONF_PATH" --path-pattern='*.conf'
    done
fi
