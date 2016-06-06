Apache layout
^^^^^^^^^^^^^

=================================================================  ====================================================================
File/Directory                                                     Description
-----------------------------------------------------------------  --------------------------------------------------------------------
``/opt/docker/etc/httpd/conf.d`` |badge-customization|             Main global configuration directory

                                                                   (automatically included files)
``/opt/docker/etc/httpd/conf.d/10-php.conf``                       PHP cgi configuration

``/opt/docker/etc/httpd/conf.d/10-error-document.conf``            Error document configuration

``/opt/docker/etc/httpd/conf.d/10-log.conf``                       Log configuration

``/opt/docker/etc/httpd/conf.d/10-server.conf``                    Basic server configuration

``/opt/docker/etc/httpd/ssl`` |badge-customization|                SSL configuration directory for

                                                                   certifications and keys


``/opt/docker/etc/httpd/ssl/server.crt`` |badge-customization|     Example SSL certification (*.vm)

``/opt/docker/etc/httpd/ssl/server.csr`` |badge-customization|     Example SSL certification request (*.vm)

``/opt/docker/etc/httpd/ssl/server.key`` |badge-customization|     Example SSL key (*.vm)

``/opt/docker/etc/httpd/vhost.common.d`` |badge-customization|     Vhost configuration directory

                                                                   (automatically included files)

``/opt/docker/etc/httpd/vhost.common.d/01-boilerplate.conf``       Placeholder configuration file

                                                                   (prevent include errors for Apache 2.2)

``/opt/docker/etc/httpd/global.conf``                              Global httpd configuration

``/opt/docker/etc/httpd/main.conf``                                Main httpd configuration

``/opt/docker/etc/httpd/php.conf`` |badge-deprecated|              Deprecated PHP configuration

``/opt/docker/etc/httpd/vhost.common.conf`` |badge-deprecated|     Deprecated vhost common include

``/opt/docker/etc/httpd/vhost.conf``                               Vhost configuration

``/opt/docker/etc/httpd/vhost.ssl.conf``                           Vhost SSL configuration

``/opt/docker/etc/supervisor.d/httpd.conf``                        Supervisord configuration file for Apache HTTPD
=================================================================  ====================================================================

.. |badge-customization| image:: https://img.shields.io/badge/hint-customization-blue.svg?style=flat
    :target: badge-customization

.. |badge-deprecated| image:: https://img.shields.io/badge/hint-deprecated-lightgrey.svg?style=flat
    :target: badge-deprecated
