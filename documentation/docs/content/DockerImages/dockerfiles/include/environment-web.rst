Web environment variables
^^^^^^^^^^^^^^^^^^^^^^^^^

====================================== ============================== ==============================================
Environment variable                   Description                    Default
====================================== ============================== ==============================================
``WEB_DOCUMENT_ROOT``                  Document root for webserver    ``/app``
``WEB_DOCUMENT_INDEX``                 Index document                 ``index.php``
``WEB_ALIAS_DOMAIN``                   Domain aliases                 ``*.vm``
``WEB_PHP_SOCKET``                     PHP-FPM socket address         ``127.0.0.1:9000`` (for php-* images)
``SERVICE_PHPFPM_OPTS``                PHP-FPM command arguments      *empty* (when php fpm is used)
``SERVICE_APACHE_OPTS``                Apache command arguments       *empty* (when apache is used)
``SERVICE_NGINX_OPTS``                 Nginx command arguments        *empty* (when nginx is used)
``SERVICE_NGINX_CLIENT_MAX_BODY_SIZE`` Nginx ``client_max_body_size`` ``50m`` (when nginx is used)
====================================== ============================== ==============================================

