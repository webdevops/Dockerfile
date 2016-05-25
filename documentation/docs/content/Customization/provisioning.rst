============
Provisioning
============

.. important:: Provision system is only available in Docker images which are based on ``webdevops/base``!

Provision Events
----------------

====================================================  ==================================================================
Provision event/tag                                   Description
----------------------------------------------------  ------------------------------------------------------------------
``bootstrap``                                         Run on Docker image creation (only run once)
``build``                                             Run on Docker image build
``onbuild``                                           Run on Docker image ONBUILD
``entrypoint``                                        Run on Docker image entrypoint execution
                                                      (only here Environment variables are available)
====================================================  ==================================================================

.. attention:: Try to avoid entrypoint provision tasks because it delays startup time.

Shell script provision
----------------------

For each provision event there is a directory for shell scripts:

- ``/opt/docker/provision/bootstrap.d/``
- ``/opt/docker/provision/build.d/``
- ``/opt/docker/provision/onbuild.d/``
- ``/opt/docker/provision/entrypoint.d/``

For customization just add your shell scripts into these directories for the simple shell script provision system.


Ansible provision
-----------------
