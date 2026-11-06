#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)
IMAGE_DISTRIBUTION=$(docker-image-info dist)
IMAGE_DISTRIBUTION_VERSION=$(docker-image-info dist-version)
IMAGE_DISTRIBUTION_VERSION_MAIN=$(echo "$IMAGE_DISTRIBUTION_VERSION" | sed 's/^\([0-9]*\).*/\1/g')

# Collect environment variables
case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
         APACHE_MAIN_PATH=/etc/apache2/
         APACHE_DOCKER_VHOST=/etc/apache2/sites-enabled/10-docker.conf
        ;;

    RedHat)
        APACHE_MAIN_PATH=/etc/httpd/
        APACHE_DOCKER_VHOST=/etc/httpd/conf.d/zzz-docker.conf
        ;;

    Alpine)
         APACHE_MAIN_PATH=/etc/apache2/
         APACHE_DOCKER_VHOST=/etc/apache2/conf.d/zzz-docker.conf
        ;;
esac

# Enable apache main config
ln -sf -- /opt/docker/etc/httpd/main.conf "$APACHE_DOCKER_VHOST"

# Ensure /var/run/apache2 exists
mkdir -p -- "/var/run/apache2"

if [[ "$IMAGE_FAMILY" == "Alpine" ]]; then
    mkdir -p -- "/run/apache2"
fi

# Maintain lock directory
if [[ "$IMAGE_FAMILY" == "Debian" ]]; then
    mkdir -p -- "/var/lock/apache2"
    chmod 0750 -- "/var/lock/apache2"
    chown www-data:www-data -- "/var/lock/apache2"
fi

APACHE_CONF_FILES=$(find "$APACHE_MAIN_PATH" -type f -iname '*.conf' -o -iname 'default*' -o -iname '*log')

# Change log to Docker stdout
go-replace --regex --regex-backrefs \
    -s '^[\s]*(CustomLog|ErrorLog|TransferLog) ([^\s]+)(.*)' -r '$1 /docker.stdout $3' \
    -s '^[\s]*(ErrorLog) ([^\s]+)(.*)' -r '$1 /docker.stderr $3' \
    --path="$APACHE_MAIN_PATH" \
    --path-regex='(.*\.conf|default.*|.*log)$'

# Switch MPM to event
if [[ "$IMAGE_FAMILY" == "RedHat" ]]; then
    go-replace --mode=line --regex --regex-backrefs \
        -s '^[\s#]*(LoadModule mpm_prefork_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule mpm_event_module.*)' -r '$1' \
        -- /etc/httpd/conf.modules.d/00-mpm.conf
fi

if [[ "$IMAGE_DISTRIBUTION" == "Ubuntu" ]] && [[ "$IMAGE_DISTRIBUTION_VERSION_MAIN" -ge 14 ]]; then
    a2enmod mpm_event
fi

if [[ "$IMAGE_DISTRIBUTION" == "Debian" ]] && [[ "$IMAGE_DISTRIBUTION_VERSION_MAIN" -ge 8 ]]; then
    a2enmod mpm_event
fi

if [[ "$IMAGE_FAMILY" == "Alpine" ]]; then
    go-replace --mode=line --regex --regex-backrefs \
        -s '^[\s#]*(LoadModule mpm_prefork_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule mpm_event_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule deflate_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule rewrite_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule logio_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule slotmem_shm_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule actions_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule expires_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule ssl_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule socache_shmcb_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule proxy_ajp_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule proxy_connect_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule proxy_balancer_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule proxy_express_module.*)' -r '#$1' \
        -s '^[\s#]*(LoadModule proxy_fcgi_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule proxy_fdpass_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule proxy_ftp_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule proxy_http_module.*)' -r '$1' \
        -s '^[\s#]*(LoadModule proxy_scgi_module.*)' -r '$1' \
        --  /etc/apache2/httpd.conf \
            /etc/apache2/conf.d/ssl.conf \
            /etc/apache2/conf.d/proxy.conf

    # Remove default vhost
    sed -i -e '1h;2,$H;$!d;g' -e 's/<VirtualHost.*<\/VirtualHost>/#-> removed vhost/g' /etc/apache2/conf.d/ssl.conf
fi

# Fix rights of ssl files
chown -R root:root /opt/docker/etc/httpd/ssl
find /opt/docker/etc/httpd/ssl -type d -exec chmod 750 {} \;
find /opt/docker/etc/httpd/ssl -type f -exec chmod 640 {} \;
