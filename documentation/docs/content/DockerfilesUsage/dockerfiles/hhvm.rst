==============
webdevops/hhvm
==============

The hhvm images are based on ``webdevops/base-app`` with HHVM cli and HHVM daemon. HHVM daemon is running on port 9000.

Docker image tags
-----------------


.. include:: include/image-tag-hhvm.rst


Docker image layout
-------------------

====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/opt/docker/etc/php/php.ini``                       WebDevOps php.ini file with basic settings
``/opt/docker/etc/php/fpm/php-fpm.conf``              PHP-FPM configuration file
``/opt/docker/etc/php/fpm/pool.d/application.conf``   PHP-FPM applocation pool configuration file
``/opt/docker/etc/supervisor.d/php-fpm.conf``         Supervisord configuration file for PHP-FPM
====================================================  ==================================================================
