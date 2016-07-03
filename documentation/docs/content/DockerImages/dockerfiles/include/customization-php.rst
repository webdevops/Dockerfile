PHP customization
^^^^^^^^^^^^^^^^^

For customization a placeholder ``/opt/docker/etc/php/php.ini`` is available which will be loaded as last
configuration file. All settings can be overwritten in this ini file.

Either use ``COPY`` inside your ``Dockerfile`` to overwrite this file or use
``RUN echo memory_limit = 128 M >> /opt/docker/etc/php/php.ini`` to set specific php.ini values.
