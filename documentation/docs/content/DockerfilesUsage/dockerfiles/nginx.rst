===============
webdevops/nginx
===============

These image extends ``webdevops/base`` with a nginx daemon which is running on port 80 and 443

Docker image tags
-----------------





Environment variables
---------------------

.. include:: include/environment-web.rst


Docker image layout
-------------------

=================================================================  ====================================================================
File/Directory                                                     Description
-----------------------------------------------------------------  --------------------------------------------------------------------
``/opt/docker/etc/nginx/conf.d``                                   Main global configuration directory (automatically included files)
``/opt/docker/etc/nginx/conf.d/10-php.conf``                       PHP cgi configuration
``/opt/docker/etc/nginx/ssl``                                      SSL configuration directory for certifications and keys
``/opt/docker/etc/nginx//ssl/server.crt``                          Example SSL certification (*.vm)
``/opt/docker/etc/nginx/ssl/server.csr``                           Example SSL certification request (*.vm)
``/opt/docker/etc/nginx/ssl/server.key``                           Example SSL key (*.vm)
``/opt/docker/etc/nginx/vhost.common.d``                           Vhost configuration directory (automatically included files)
``/opt/docker/etc/nginx/vhost.common.d/10-location-root.conf``     Redirect requests to DOCUMENT_INDEX
``/opt/docker/etc/nginx/vhost.common.d/10-php.conf``               PHP cgi configuration for vhost
``/opt/docker/etc/nginx/global.conf``                              Global nginx configuration
``/opt/docker/etc/nginx/main.conf``                                Main Nginx configuration
``/opt/docker/etc/nginx/php.conf``                                 Deprecated PHP configuration
``/opt/docker/etc/nginx/vhost.common.conf``                        Deprecated vhost common include
``/opt/docker/etc/nginx/vhost.conf``                               Vhost configuration
``/opt/docker/etc/nginx/vhost.ssl.conf``                           Vhost SSL configuration
=================================================================  ====================================================================
