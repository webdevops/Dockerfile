# setup user env
FPM_POOL_CONF="/opt/docker/etc/php/fpm/pool.d/application.conf"

## Setup container uid
if [[ -n "$CONTAINER_UID" ]]; then
       echo "Setting php-fpm user to $CONTAINER_UID"
       go-replace --mode=line --regex \
           -s '^[\s;]*user[\s]*='  -r "user = $CONTAINER_UID" \
           -s '^[\s;]*group[\s]*=' -r "group = $CONTAINER_UID" \
           --path=/opt/docker/etc/php/fpm/ \
           --path-pattern='*.conf'
fi
