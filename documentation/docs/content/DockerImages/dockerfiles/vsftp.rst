===============
webdevops/vsftp
===============

These image extends ``webdevops/base`` and provides a standalone vsftp server running on port 20 and 21.

.. include:: include/general-supervisor.rst

Docker image tags
-----------------

====================== ==========================
Tag                    Distribution name
====================== ==========================
``latest``             Ubuntu 16.04 xenial (LTS)
====================== ==========================


Environment variables
---------------------

========================= ==================================== ==================
Environment variable      Description                          Default
========================= ==================================== ==================
``FTP_USER``              FTP account username                 ``application``
``FTP_PASSWORD``          FTP account password                 ``application``
``FTP_UID``               FTP account uid                      ``1000``
``FTP_GID``               FTP account gid                      ``1000``
``FTP_PATH``              FTP account home path                ``/data/ftp/``
``SERVICE_VSFTP_OPTS``    VSFTP command arguments              *empty*
========================= ==================================== ==================
