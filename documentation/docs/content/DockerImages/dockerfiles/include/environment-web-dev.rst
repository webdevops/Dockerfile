========================== ============================ ==============================================
Environment variable       Description                  Default
========================== ============================ ==============================================
``WEB_DOCUMENT_ROOT``      Document root for webserver  ``/app``
``WEB_DOCUMENT_INDEX``     Index document               ``index.php``
``WEB_ALIAS_DOMAIN``       Domain aliases               ``*.vm``
``WEB_PHP_SOCKET``         PHP-FPM socket address       ``127.0.0.1:9000`` (for php-* images)
``WEB_NO_CACHE_PATTERN``   RegExp of files which should ``\.(css|js|gif|png|jpg|svg|json|xml)$``
                           be delivered by webserver as
                           non cacheable to browser
``PHP_DEBUGGER``           Specifies which php debugger *empty* (eg. ``xdebug``, ``blackfire`` or
                           should be active             ``none``)
========================== ============================ ==============================================
