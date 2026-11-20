# setup user env
FPM_POOL_CONF="/opt/docker/etc/php/fpm/pool.d/application.conf"
# the pool file of the variables, which the entrypoint writes at every start; PHP-FPM reads it after application.conf
FPM_POOL_ENVIRONMENT_CONF="/opt/docker/etc/php/fpm/pool.d/zz-environment.conf"

## Setup container uid
## The user goes to zz-environment.conf, so that application.conf stays as it is, also as a read-only mount.
if [[ -n "$CONTAINER_UID" ]]; then
       echo "Setting php-fpm user to $CONTAINER_UID"
       if [[ ! -f "$FPM_POOL_ENVIRONMENT_CONF" ]]; then
           echo '[www]' > "$FPM_POOL_ENVIRONMENT_CONF"
       fi
       go-replace --mode=lineinfile --regex \
           -s '^user[\s]*='  -r "user = $CONTAINER_UID" \
           -s '^group[\s]*=' -r "group = $CONTAINER_UID" \
           -- "$FPM_POOL_ENVIRONMENT_CONF"
fi
