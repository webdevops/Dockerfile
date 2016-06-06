PHP layout
^^^^^^^^^^

=================================================================  ====================================================================
File/Directory                                                     Description
-----------------------------------------------------------------  --------------------------------------------------------------------
``/opt/docker/etc/php/php.webdevops.ini``                          PHP settings from WebDevOps image
``/opt/docker/etc/php/php.ini`` |badge-customization|              php.ini for custom settings
``/opt/docker/etc/php/fpm/php-fpm.conf``                           PHP-FPM main configuration file
``/opt/docker/etc/php/fpm/pool.d/application.conf``                Application PHP-FPM pool configuration file
``/opt/docker/etc/supervisor.d/php-fpm.conf``                      Supervisord configuration file for PHP-FPM
=================================================================  ====================================================================

.. |badge-customization| image:: https://img.shields.io/badge/hint-customization-blue.svg?style=flat
   :target: badge-customization

.. |badge-deprecated| image:: https://img.shields.io/badge/hint-deprecated-lightgrey.svg?style=flat
   :target: badge-deprecated
