Base environment variables
^^^^^^^^^^^^^^^^^^^^^^^^^^


============================ ============================= ================================
Environment variable          Description                   Default
============================ ============================= ================================
``LOG_STDOUT``               Destination of daemon output  *empty* (stdout)
``LOG_STDERR``               Destination of daemon errors  *empty* (stdout)
``SERVICE_CRON_OPTS``        cron daemon arguments         *empty* (when syslog is used)
``SERVICE_DNSMASQ_OPTS``     dnsmasq daemon arguments      *empty* (when syslog is used)
``SERVICE_DNSMASQ_USER``     dnsmasq effective user        ``root``
``SERVICE_POSTFIX_OPTS``     postfix daemon arguments      *empty* (when syslog is used)
``SERVICE_SSH_OPTS``         ssh daemon arguments          *empty* (when syslog is used)
``SERVICE_SUPERVISOR_OPTS``  supervisor daemon arguments   *empty* (when syslog is used)
``SERVICE_SUPERVISOR_USER``  supervisor effective user     ``root``
``SERVICE_SYSLOG_OPTS``      syslog daemon arguments       *empty* (when syslog is used)
============================ ============================= ================================
