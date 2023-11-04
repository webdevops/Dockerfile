Nginx customization
^^^^^^^^^^^^^^^^^^^

This image has two directories for configuration files which will be automatic loaded.

For global configuration options the directory ``/opt/docker/etc/nginx/conf.d`` can be used.
For vhost configuration options the directory ``/opt/docker/etc/nginx/vhost.common.conf`` can be used.

Any ``*.conf`` files inside these directories will be included either global or the vhost section.
