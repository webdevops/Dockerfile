======================
webdevops/mail-catcher
======================

These image extends ``webdevops/base`` with a postfix daemon which is running on port 25 and dovecot on IMAP.

This images catches all emails sent to it and stores them locally. These mails are available via IMAP.

Docker image tags
-----------------

====================== ==========================
Tag                    Distribution name
====================== ==========================
``latest``             Ubuntu 16.04 xenial (LTS)
====================== ==========================


Environment variables
---------------------

.. include:: include/environment-base-app.rst


Docker image layout
-------------------
