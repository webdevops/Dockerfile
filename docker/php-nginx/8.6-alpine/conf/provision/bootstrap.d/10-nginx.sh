#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

# Remove daemon statement (will be added as command line argument)
go-replace --mode=lineinfile --regex --regex-backrefs \
    -s '^[\s#]*daemon ' -r '' \
    -- /etc/nginx/nginx.conf

go-replace --mode=line --regex --regex-backrefs \
    -s '^([ \t]*access_log)[ \t]*([^\t ;]+)(.*;)$' -r '$1 /docker.stdout $3' \
    -s '^([ \t]*error_log)[ \t]*([^\t ;]+)(.*;)$' -r '$1 /docker.stderr $3' \
    --  /etc/nginx/nginx.conf

# Enable nginx main config
mkdir -p /etc/nginx/conf.d/
ln -sf /opt/docker/etc/nginx/main.conf /etc/nginx/conf.d/10-docker.conf

rm -f \
    /etc/nginx/sites-enabled/default \
    /etc/nginx/conf.d/default.conf

if [[ "$IMAGE_FAMILY" == "RedHat" ]] || [[ "$IMAGE_FAMILY" == "Alpine" ]]; then
    ln -sf /opt/docker/etc/nginx/nginx.conf /etc/nginx/nginx.conf
fi

# Clear log dir
rm -rf /var/lib/nginx/logs
mkdir -p /var/lib/nginx/logs

# Set log to stdout/stderr
ln -sf /var/lib/nginx/logs/access.log /docker.stdout
ln -sf /var/lib/nginx/logs/error.log /docker.stderr

# Self-signed certificate for *.vm, new with every build of the image, valid for ten years
mkdir -p /opt/docker/etc/nginx/ssl
openssl req -new -newkey rsa:2048 -nodes -subj '/CN=*.vm' \
    -keyout /opt/docker/etc/nginx/ssl/server.key -out /opt/docker/etc/nginx/ssl/server.csr
openssl x509 -req -days 3650 -in /opt/docker/etc/nginx/ssl/server.csr -signkey /opt/docker/etc/nginx/ssl/server.key \
    -extfile <(echo 'subjectAltName = DNS:*.vm') -out /opt/docker/etc/nginx/ssl/server.crt

# Fix rights of ssl files
chown -R root:root /opt/docker/etc/nginx/ssl
find /opt/docker/etc/nginx/ssl -type d -exec chmod 750 {} \;
find /opt/docker/etc/nginx/ssl -type f -exec chmod 640 {} \;
