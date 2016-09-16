Logstash layout
^^^^^^^^^^^^^^^

==================================================================  ====================================================================
File/Directory                                                      Description
------------------------------------------------------------------  --------------------------------------------------------------------
``/usr/local/logstash/logstash.d``                                  Directory that contains the configuration for logstash
``/opt/docker/etc/logstash/10-inputs.conf``  |badge-customization|  Define default source for logstash
``/opt/docker/etc/logstash/50-filters.conf`` |badge-customization|  Define rules to performs intermediary processing on an event
``/opt/docker/etc/logstash/99-outputs.conf`` |badge-customization|  Define default output for logstash
``/opt/docker/bin/service.d/logstash.sh``                           Script that launches logstash Service
``/opt/docker/etc/supervisor.d/logstash.conf``                      Supervisord configuration file for logstash
==================================================================  ====================================================================

.. |badge-customization| image:: https://img.shields.io/badge/hint-customization-blue.svg?style=flat
   :target: badge-customization