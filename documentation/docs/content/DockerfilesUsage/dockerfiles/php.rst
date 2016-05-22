=============
webdevops/php
=============

The php images are based on ``webdevops/base-app`` with PHP cli and PHP-FPM. PHP-FPM is running on port 9000.

.. include:: include/info-php-production.rst

Docker image tags
-----------------


.. include:: include/image-tag-php.rst


Docker image layout
-------------------

====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/opt/docker/etc/php/php.ini``                       Placeholder php.ini file for custom configuration
``/opt/docker/etc/php/php.webdevops.ini``             WebDevOps php.ini file with basic settings
``/opt/docker/etc/php/fpm/php-fpm.conf``              PHP-FPM configuration file
``/opt/docker/etc/php/fpm/pool.d/application.conf``   PHP-FPM applocation pool configuration file
``/opt/docker/etc/supervisor.d/php-fpm.conf``         Supervisord configuration file for PHP-FPM
====================================================  ==================================================================
