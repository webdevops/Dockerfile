PHP development environment variables
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

============================================= ========================================= ==============================================
Environment variable                          Description                               Default
============================================= ========================================= ==============================================
``WEB_DOCUMENT_ROOT``                         Document root for webserver               ``/app``
``WEB_DOCUMENT_INDEX``                        Index document                            ``index.php``
``WEB_ALIAS_DOMAIN``                          Domain aliases                            ``*.vm``
``WEB_PHP_SOCKET``                            PHP-FPM socket address                    ``127.0.0.1:9000`` (for php-* images)
``WEB_NO_CACHE_PATTERN``                      RegExp of files which should              ``\.(css|js|gif|png|jpg|svg|json|xml)$``
                                              be delivered by webserver as
                                              non cacheable to browser
``PHP_DEBUGGER``                              Specifies which php debugger              ``xdebug`` (eg. ``xdebug``, ``blackfire`` or
                                              should be active                          ``none``)
``XDEBUG_REMOTE_AUTOSTART``                   php.ini value for                         ``none``
                                              ``xdebug.remote_autostart``
``XDEBUG_REMOTE_CONNECT_BACK``                php.ini value for                         ``none``
                                              ``xdebug.remote_connect_back``
``XDEBUG_REMOTE_HOST``                        php.ini value for                         ``none``
                                              ``xdebug.remote_host``
``XDEBUG_REMOTE_PORT``                        php.ini value for                         ``none``
                                              ``xdebug.remote_port``
``XDEBUG_MAX_NESTING_LEVEL``                  php.ini value for                         ``none``
                                              ``xdebug.max_nesting_level``
``XDEBUG_IDE_KEY``                            php.ini value for                         ``none``
                                              ``xdebug.idekey``
``XDEBUG_PROFILER_ENABLE``                    php.ini value for                         ``none``
                                              ``xdebug.profiler_enable``
``XDEBUG_PROFILER_ENABLE_TRIGGER``            php.ini value for                         ``none``
                                              ``xdebug.profiler_enable_trigger``
``XDEBUG_PROFILER_ENABLE_TRIGGER_VALUE``      php.ini value for                         ``none``
                                              ``xdebug.profiler_enable_trigger_value``
``XDEBUG_PROFILER_OUTPUT_DIR``                php.ini value for                         ``none``
                                              ``xdebug.profiler_output_dir``
``XDEBUG_PROFILER_OUTPUT_NAME``               php.ini value for                         ``none``
                                              ``xdebug.profiler_output_name``
``BLACKFIRE_SERVER_ID``                       php.ini value for                         ``none``
                                              ``blackfire.server_id``
``BLACKFIRE_SERVER_TOKEN``                    php.ini value for                         ``none``
                                              ``blackfire.server_token``
``SERVICE_BLACKFIRE_AGENT_OPTS``              Blackfire agent command arguments         *empty*
============================================= ========================================= ==============================================
