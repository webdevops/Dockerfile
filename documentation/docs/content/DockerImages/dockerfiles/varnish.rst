=================
webdevops/varnish
=================

These image extends ``webdevops/base`` and provides a standalone varnish server running on port 80.

Docker image tags
-----------------

====================== ==========================
Tag                    Distribution name
====================== ==========================
``latest``             Alpine 3
====================== ==========================


Environment variables
---------------------

========================= ==================================== =============
Environment variable      Description                          Default
========================= ==================================== =============
``VARNISH_PORT``          Listening port                       ``80``
``VARNISH_CONFIG``        Custom configuration file            `empty`
``VARNISH_STORAGE``       Storage cache setting                ``malloc,128m``
``VARNISH_OPTS``          Additional varnish command options   `empty`
``VARNISH_BACKEND_HOST``  Backend server hostname              `empty`
``VARNISH_BACKEND_PORT``  Backend server port                  ``80``
========================= ==================================== =============
