================
webdevops/apache
================

These image extends ``webdevops/base`` with a apache daemon which is running on port 80 and 443

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
``/opt/docker/etc/httpd/conf.d``                                   Main global configuration directory
                                                                   (automatically included files)
                                                                   |badge-customization|
``/opt/docker/etc/httpd/conf.d/10-php.conf``                       PHP cgi configuration
``/opt/docker/etc/httpd/conf.d/10-error-document.conf``            Error document configuration
``/opt/docker/etc/httpd/conf.d/10-log.conf``                       Log configuration
``/opt/docker/etc/httpd/conf.d/10-server.conf``                    Basic server configuration
``/opt/docker/etc/httpd/ssl``                                      SSL configuration directory for certifications and keys
``/opt/docker/etc/httpd//ssl/server.crt``                          Example SSL certification (*.vm)
``/opt/docker/etc/httpd/ssl/server.csr``                           Example SSL certification request (*.vm)
``/opt/docker/etc/httpd/ssl/server.key``                           Example SSL key (*.vm)
``/opt/docker/etc/httpd/vhost.common.d``                           Vhost configuration directory (automatically included files)
``/opt/docker/etc/httpd/vhost.common.d/01-boilerplate.conf``       Placeholder configuration file (prevent include errors for Apache 2.2)
``/opt/docker/etc/httpd/global.conf``                              Global httpd configuration
``/opt/docker/etc/httpd/main.conf``                                Main httpd configuration
``/opt/docker/etc/httpd/php.conf``                                 Deprecated PHP configuration
``/opt/docker/etc/httpd/vhost.common.conf``                        Deprecated vhost common include
``/opt/docker/etc/httpd/vhost.conf``                               Vhost configuration
``/opt/docker/etc/httpd/vhost.ssl.conf``                           Vhost SSL configuration
=================================================================  ====================================================================

.. |badge-customization| image:: https://img.shields.io/badge/hint-customization-green.svg?style=flat
:target: badge-customization
