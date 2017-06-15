PHP.ini variables
^^^^^^^^^^^^^^^^^

You can specify eg. ``php.memory_limit=256M`` as dyanmic env variable which will sets ``memory_limit = 256M`` as php setting.

============================================= ========================================= ==============================================
Environment variable                          Description                               Default
============================================= ========================================= ==============================================
``php.{setting-key}``                         Sets the ``{setting-key}`` as php setting
``PHP_DATE_TIMEZONE``                         ``date.timezone``                         ``UTC``
``PHP_DISPLAY_ERRORS``                        ``display_errors``                        ``0``
``PHP_MEMORY_LIMIT``                          ``memory_limit``                          ``512M``
``PHP_MAX_EXECUTION_TIME``                    ``max_execution_time``                    ``300``
``PHP_POST_MAX_SIZE``                         ``post_max_size``                         ``50M``
``PHP_UPLOAD_MAX_FILESIZE``                   ``upload_max_filesize``                   ``50M``
``PHP_OPCACHE_MEMORY_CONSUMPTION``            ``opcache.memory_consumption``            ``256``
``PHP_OPCACHE_MAX_ACCELERATED_FILES``         ``opcache.max_accelerated_files``         ``7963``
``PHP_OPCACHE_VALIDATE_TIMESTAMPS``           ``opcache.validate_timestamps``           ``default``
``PHP_OPCACHE_REVALIDATE_FREQ``               ``opcache.revalidate_freq``               ``default``
``PHP_OPCACHE_INTERNED_STRINGS_BUFFER``       ``opcache.interned_strings_buffer``       ``16``
============================================= ========================================= ==============================================

PHP FPM  variables
^^^^^^^^^^^^^^^^^^

You can specify eg. ``fpm.pool.pm.max_requests=1000`` as dyanmic env variable which will sets ``pm.max_requests = 1000`` as fpm pool setting.
The prefix ``fpm.pool`` is for pool settings and ``fpm.global`` for global master process settings.

============================================= ========================================= ==============================================
Environment variable                          Description                               Default
============================================= ========================================= ==============================================
``fpm.global.{setting-key}``                  Sets the ``{setting-key}`` as fpm global
                                              setting for the master process
``fpm.pool.{setting-key}``                    Sets the ``{setting-key}`` as fpm pool
                                              setting
``FPM_PROCESS_MAX``                           ``process.max``                           ``distribution default``
``FPM_PM_MAX_CHILDREN``                       ``pm.max_children``                       ``distribution default``
``FPM_PM_START_SERVERS``                      ``pm.start_servers``                      ``distribution default``
``FPM_PM_MIN_SPARE_SERVERS``                  ``pm.min_spare_servers``                  ``distribution default``
``FPM_PM_MAX_SPARE_SERVERS``                  ``pm.max_spare_servers``                  ``distribution default``
``FPM_PROCESS_IDLE_TIMEOUT``                  ``pm.process_idle_timeout``               ``distribution default``
``FPM_MAX_REQUESTS``                          ``pm.max_requests``                       ``distribution default``
``FPM_REQUEST_TERMINATE_TIMEOUT``             ``request_terminate_timeout``             ``distribution default``
``FPM_RLIMIT_FILES``                          ``rlimit_files``                          ``distribution default``
``FPM_RLIMIT_CORE``                           ``rlimit_core``                           ``distribution default``
============================================= ========================================= ==============================================
