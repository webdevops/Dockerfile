Apache customization
^^^^^^^^^^^^^^^^^^^^

This image has two directories for configuration files which will be automatic loaded.

For global configuration options the directory ``/opt/docker/etc/httpd/conf.d`` can be used.
For vhost configuration options the directory ``/opt/docker/etc/httpd/vhost.common.d`` can be used.

Any ``*.conf`` files inside these direcories will be included either global or the vhost section.
