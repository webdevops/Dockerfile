======================
webdevops/mail-catcher
======================

These image extends ``webdevops/base`` with a postfix daemon which is running on port 25 and dovecot on IMAP.

This images catches all emails sent to it and stores them locally. These mails are available via IMAP and web (roundcube)

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

.. include:: include/environment-base.rst
.. include:: include/environment-base-app.rst


Mail sandbox environment variables
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

====================== ============================= =============
Environment variable   Description                   Default
====================== ============================= =============
``MAILBOX_USERNAME``   IMAP user                     dev
``MAILBOX_PASSWORD``   IMAP user password            dev
====================== ============================= =============

Docker image layout
-------------------
