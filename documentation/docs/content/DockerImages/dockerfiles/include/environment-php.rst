PHP.ini variables
^^^^^^^^^^^^^^^^^

============================================= ========================================= ==============================================
Environment variable                          Description                               Default
============================================= ========================================= ==============================================
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

PHP FPM variables
^^^^^^^^^^^^^^^^^

============================================= ========================================= ==============================================
Environment variable                          Description                               Default
============================================= ========================================= ==============================================
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
