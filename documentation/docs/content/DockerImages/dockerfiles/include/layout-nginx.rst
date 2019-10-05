Nginx layout
^^^^^^^^^^^^

=================================================================  ====================================================================
File/Directory                                                     Description
-----------------------------------------------------------------  --------------------------------------------------------------------
``/opt/docker/etc/nginx/conf.d`` |badge-customization|             Main global configuration directory

                                                                   (automatically included files)
``/opt/docker/etc/nginx/conf.d/10-php.conf``                       PHP cgi configuration

``/opt/docker/etc/nginx/ssl`` |badge-customization|                SSL configuration directory for

                                                                   certifications and keys
``/opt/docker/etc/nginx/ssl/server.crt`` |badge-customization|     Example SSL certification (*.vm)

``/opt/docker/etc/nginx/ssl/server.csr`` |badge-customization|     Example SSL certification request (*.vm)

``/opt/docker/etc/nginx/ssl/server.key`` |badge-customization|     Example SSL key (*.vm)

``/opt/docker/etc/nginx/vhost.common.d`` |badge-customization|     Vhost configuration directory

                                                                   (automatically included files)

``/opt/docker/etc/nginx/vhost.common.d/10-location-root.conf``     Redirect requests to DOCUMENT_INDEX

``/opt/docker/etc/nginx/vhost.common.d/10-php.conf``               PHP cgi configuration for vhost

``/opt/docker/etc/nginx/global.conf``                              Global nginx configuration

``/opt/docker/etc/nginx/main.conf``                                Main Nginx configuration

``/opt/docker/etc/nginx/php.conf`` |badge-deprecated|              Deprecated PHP configuration

``/opt/docker/etc/nginx/vhost.common.conf`` |badge-deprecated|     Deprecated vhost common include

``/opt/docker/etc/nginx/vhost.conf``                               Vhost configuration

``/opt/docker/etc/nginx/vhost.ssl.conf``                           Vhost SSL configuration

``/opt/docker/etc/supervisor.d/nginx.conf``                        Supervisord configuration file for Nginx
=================================================================  ====================================================================

.. |badge-customization| image:: https://img.shields.io/badge/hint-customization-blue.svg?style=flat
   :target: badge-customization

.. |badge-deprecated| image:: https://img.shields.io/badge/hint-deprecated-lightgrey.svg?style=flat
   :target: badge-deprecated
