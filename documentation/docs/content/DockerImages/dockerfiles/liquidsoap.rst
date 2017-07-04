====================
webdevops/liquidsoap
====================

The liquidsoap images are based on ``webdevops/base`` with liquidsoap multimedia streaming server with most plugins.

.. include:: include/general-supervisor.rst

Docker image tags
-----------------

====================== =============================================
Tag                    Distribution name
====================== =============================================
``latest``             Based on `webdevops/base:latest` (Ubuntu)
====================== =============================================

Environment variables
---------------------

.. include:: include/environment-base.rst


Liquisoap environment variables
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
====================================================== ===================================== ==============================================
Environment variable                                   Description                           Default
====================================================== ===================================== ==============================================
``LIQUIDSOAP_USER``                                    Daemon user ID                        ``liquidsoap``
``LIQUIDSOAP_TELNET``                                  Open telnet (port 1234)               ``1`` (enabled)
``LIQUIDSOAP_SCRIPT``                                  Configuration script for liquidsoap   ``/opt/docker/etc/liquidsoap/default.liq``
``LIQUIDSOAP_TEMPLATE``                                Apply template to config script       ``1/opt/docker/etc/liquidsoap/default.liq``

``LIQUIDSOAP_STREAM_INPUT``                            Input stream (eg. icecast)            ``http://icecast:8000/live``

``LIQUIDSOAP_PLAYLIST_DEFAULT``                        Default stream when no other stream   ``audio_to_stereo(single('/opt/docker/etc/liquidsoap/default.mp3'))``
                                                       is active

``LIQUIDSOAP_PLAYLIST_DAY``                            Day input stream/playlist             ``playlist('/opt/docker/etc/liquidsoap/playlist-day.pls')``
``LIQUIDSOAP_PLAYLIST_DAY_TIMERANGE``                  Timerange for day playlist            ``4h-2h``

``LIQUIDSOAP_PLAYLIST_NIGHT``                          Night input stream/playlist           ``playlist('/opt/docker/etc/liquidsoap/playlist-night.pls')``
``LIQUIDSOAP_PLAYLIST_NIGHT_TIMERANGE``                Timerange for night playlist          ``2h-14h``

``LIQUIDSOAP_OUTPUT``                                  Output stream (eg. icecast)           ``output.icecast(%mp3(bitrate=128),host='localhost',port=8000,password='secretpassword',mount='liquidsoap-128',name=META_name,genre=META_genre,url=META_url,description=META_desc,ALL_input)``

``LIQUIDSOAP_OUTPUT_1`` ... ``LIQUIDSOAP_OUTPUT_20``   More output stream lines              *empty*


``LIQUIDSOAP_META_NAME``                               Station name                          ``Liquidsoap Docker``
``LIQUIDSOAP_META_GENRE``                              Station genre                         *empty*
``LIQUIDSOAP_META_URL``                                Station url                           *empty*
``LIQUIDSOAP_META_DESCRIPTION``                        Station description                   *empty*
====================================================== ===================================== ==============================================

