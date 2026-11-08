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

# Without IPv6 in the container nginx cannot listen on [::], so it listens on IPv4 only.
# Only files in the container's own file system are changed. Mounted files (bind mounts,
# volumes, ConfigMaps) are left alone, so a file in the user's project is never changed.
if [[ ! -e /proc/net/if_inet6 ]]; then
    NGINX_MOUNTS="$(awk '$5 != "/" { print $5 }' /proc/self/mountinfo)"
    NGINX_CONF_SEEN=""
    # Hidden directories are skipped: nginx does not include them, and Kubernetes keeps
    # the files of a ConfigMap there (..data) behind the visible symlinks
    while IFS= read -r -d '' NGINX_CONF; do
        # Handle every file once, also when it is reachable through several symlinks
        NGINX_CONF_REAL="$(readlink -f "$NGINX_CONF")"
        case "$NGINX_CONF_SEEN" in *$'\n'"$NGINX_CONF_REAL"$'\n'*) continue ;; esac
        NGINX_CONF_SEEN="$NGINX_CONF_SEEN"$'\n'"$NGINX_CONF_REAL"$'\n'

        if ! grep -qE '^[[:space:]]*listen[[:space:]]+\[::\]' "$NGINX_CONF"; then
            continue
        fi

        NGINX_CONF_MOUNTED=0
        while IFS= read -r NGINX_MOUNT; do
            case "$NGINX_CONF" in "$NGINX_MOUNT"|"$NGINX_MOUNT"/*) NGINX_CONF_MOUNTED=1 ;; esac
            case "$NGINX_CONF_REAL" in "$NGINX_MOUNT"|"$NGINX_MOUNT"/*) NGINX_CONF_MOUNTED=1 ;; esac
        done <<< "$NGINX_MOUNTS"

        if [[ "$NGINX_CONF_MOUNTED" -eq 1 ]]; then
            echo "WARNING: IPv6 is not available, but $NGINX_CONF is mounted and contains listen [::]; nginx will not start" >&2
            continue
        fi

        echo "IPv6 is not available, removing the IPv6 listen directives from $NGINX_CONF"
        # Rewrite in place, so a symlinked file keeps its link
        NGINX_CONF_TMP=""
        if ! { NGINX_CONF_TMP="$(mktemp)" \
            && sed -E '/^[[:space:]]*listen[[:space:]]+\[::\]/d' "$NGINX_CONF" > "$NGINX_CONF_TMP" \
            && cat "$NGINX_CONF_TMP" > "$NGINX_CONF"; }; then
            echo "WARNING: Cannot change $NGINX_CONF; nginx will not start without IPv6" >&2
        fi
        if [[ -n "$NGINX_CONF_TMP" ]]; then
            rm -f -- "$NGINX_CONF_TMP"
        fi
    done < <(find -L /opt/docker/etc/nginx/ -mindepth 1 -name '.*' -prune -o -type f -name '*.conf' -print0)
    unset NGINX_MOUNTS NGINX_MOUNT NGINX_CONF NGINX_CONF_REAL NGINX_CONF_MOUNTED NGINX_CONF_TMP NGINX_CONF_SEEN
fi
