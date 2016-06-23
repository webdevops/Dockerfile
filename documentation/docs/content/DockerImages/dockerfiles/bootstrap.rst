===================
webdevops/bootstrap
===================

Bootstrap images contains our baselayout (some basic scripts for secure and small package installations and handling) and a basic toolset.
It will also install Ansible into the container for future provisioning of the container.

Docker image tags
-----------------

.. include:: include/image-tag-base.rst


Baselayout scripts
------------------

====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/usr/local/bin/apk-install``                        **Alpine**: Updates package cache, install packages and clears package cache
``/usr/local/bin/apk-upgrade``                        **Alpine**: Run package upgrade
``/usr/local/bin/apt-install``                        **Debian family**: Updates package cache, install packages and clears package cache
``/usr/local/bin/apt-upgrade``                        **Debian family**: Run package upgrade
``/usr/local/bin/yum-install``                        **RedHat family**: Updates package cache, install packages and clears package cache
``/usr/local/bin/yum-upgrade``                        **RedHat family**: Run package upgrade
``/usr/local/bin/generate-locales``                   Generate locales
``/usr/local/bin/rpl``                                Script which can replace text in files
``/usr/local/bin/service``                            Supervisord service wrapper script (like ``service`` in Debian)
====================================================  ==================================================================
