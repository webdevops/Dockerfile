# Replace markers
go-replace \
    -s "<DOCUMENT_INDEX>" -r "$WEB_DOCUMENT_INDEX" \
    -s "<DOCUMENT_ROOT>" -r "$WEB_DOCUMENT_ROOT" \
    -s "<ALIAS_DOMAIN>" -r "$WEB_ALIAS_DOMAIN" \
    -s "<SERVERNAME>" -r "$HOSTNAME" \
    -s "<PHP_SOCKET>" -r "$WEB_PHP_SOCKET" \
    -s "<PHP_TIMEOUT>" -r "$WEB_PHP_TIMEOUT" \
    --path=/opt/docker/etc/httpd/ \
    --path-pattern='*.conf' \
    --ignore-empty

if [[ -z "$WEB_PHP_SOCKET" ]]; then
    ## WEB_PHP_SOCKET is not set, remove PHP files
    rm -f -- /opt/docker/etc/httpd/conf.d/10-php.conf
fi

# SERVICE_APACHE_ACCESS_LOG and SERVICE_APACHE_ERROR_LOG: the access log and the error log of Apache, which the image
# sends to /docker.stdout and /docker.stderr, go to these files instead, for example /dev/null to turn a log off. Only
# the lines that still point to /docker.stdout or /docker.stderr change, so a log of your own stays where you put it.
for APACHE_CONF_PATH in /opt/docker/etc/httpd/ /etc/apache2/ /etc/httpd/; do
    if [[ ! -d "$APACHE_CONF_PATH" ]]; then
        continue
    fi

    if [[ -n "${SERVICE_APACHE_ACCESS_LOG:-}" ]]; then
        go-replace --mode=line --regex --regex-backrefs \
            -s '^([ \t]*(CustomLog|TransferLog)[ \t]+)/docker\.stdout([ \t].*)?$' -r "\${1}${SERVICE_APACHE_ACCESS_LOG}\${3}" \
            --path="$APACHE_CONF_PATH" --path-regex='(.*\.conf|default.*|.*log)$'
    fi

    if [[ -n "${SERVICE_APACHE_ERROR_LOG:-}" ]]; then
        go-replace --mode=line --regex --regex-backrefs \
            -s '^([ \t]*ErrorLog[ \t]+)/docker\.stderr([ \t].*)?$' -r "\${1}${SERVICE_APACHE_ERROR_LOG}\${2}" \
            --path="$APACHE_CONF_PATH" --path-regex='(.*\.conf|default.*|.*log)$'
    fi
done
