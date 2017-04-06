# setup user env
FPM_POOL_CONF="/opt/docker/etc/php/fpm/pool.d/application.conf"

## Setup container uid
if [[ -n "$CONTAINER_UID" ]]; then
    sed -i "s/user[ ]*=.*/user = ${CONTAINER_UID}/g"   "$FPM_POOL_CONF"
    sed -i "s/group[ ]*=.*/group = ${CONTAINER_UID}/g" "$FPM_POOL_CONF"
fi
