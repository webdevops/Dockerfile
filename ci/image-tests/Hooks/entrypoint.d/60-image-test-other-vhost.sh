# Image tests: with IMAGE_TEST_OTHER_VHOST set, adds a second virtual host before the web server starts, which answers
# 410 to every host name that the default virtual host does not have, see WebEnvironmentChecks
if [[ -n "${IMAGE_TEST_OTHER_VHOST:-}" ]]; then
    if [[ -d /opt/docker/etc/nginx/conf.d ]]; then
        printf 'server {\n    listen 80;\n    server_name ~^.+$;\n    return 410;\n}\n' > /opt/docker/etc/nginx/conf.d/image-test-other-vhost.conf
    fi

    if [[ -d /opt/docker/etc/httpd/conf.d ]]; then
        printf '<VirtualHost *:80>\n    ServerName other.image-test.example\n    Redirect gone /\n</VirtualHost>\n' > /opt/docker/etc/httpd/conf.d/image-test-other-vhost.conf
    fi
fi
