============
Provisioning
============

.. important:: Provision system is only available in Docker images which are based on ``webdevops/base``!


Custom entrypoint scripts
-------------------------

Shell scripts (``*.sh``) for container startup can be placed inside following directories:

- ``/entrypoint.d/``
- ``/opt/docker/provision/entrypoint.d/``

These files (``*.sh``) will be executed automatically.


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

For Ansible the provision events are available as tags. The roles are located inside ``/opt/docker/provision/roles/``
and must be registred with the provision system:

.. code-block:: bash

    /opt/docker/bin/provision add --tag bootstrap --role my-own-role


Multiple tags can be defined with multiple ``--tag`` options:

.. code-block:: bash

    /opt/docker/bin/provision add --tag bootstrap --tag build --role my-own-role

There is a pritory system for roles in which order they should be executed, default priority is 100:

.. code-block:: bash

    ## run before
    /opt/docker/bin/provision add --tag bootstrap --priority 40 --role my-own-role-first

    ## run with normal priority
    /opt/docker/bin/provision add --tag bootstrap --role my-own-role

    ## run after
    /opt/docker/bin/provision add --tag bootstrap --priority 200 --role my-own-role-last

It's also possible to run one role with the provision command:

.. code-block:: bash

    /opt/docker/bin/provision run --tag bootstrap --role my-own-role
