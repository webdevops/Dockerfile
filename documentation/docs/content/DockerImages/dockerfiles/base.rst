==============
webdevops/base
==============

Our application base container contains some general tools, the provisioning system (Ansible), a preconfgured
modular ``supervisord`` and a modular `entrypoint` script.

.. include:: include/general-supervisor.rst

Docker image tags
-----------------

.. include:: include/image-tag-base.rst


Environment variables
---------------------

.. include:: include/environment-base.rst

Entrypoint
----------

The entrypoint script is located in ``/opt/docker/bin/entrypoint.sh`` and will also start the entrypoint provisioning
before running the requested ``CMD``.

Based on ``CMD`` the entrypoint script is trying to find the appropriate worker script located in
``/opt/docker/bin/entrypoint.d`` and executes it. It must matches the ``CMD`` and if there is no appropriate
worker script the entrypoint falls back to ``/opt/docker/bin/entrypoint.d/default.sh`` which just executes
the specified ``CMD``.


This approach allows a modular entrypoint and also allows to directly jump into a container
(eg. with ``docker run -ti webdevops/apache bash``) without uploading or modifing the entrypoint.

Example for starting ``supervisord`` (executed by ``entrypoint.d/supervisord.sh``):

.. code-block:: docker

    ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
    CMD ["supervisord"]


====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/opt/docker/bin/entrypoint.sh``                     Entrypoint script for ``ENTRYPOINT`` instruction in Dockerfile
``/opt/docker/bin/entrypoint.d/default.sh``           Default worker script, will execute the defined cmd
``/opt/docker/bin/entrypoint.d/cli.sh``               Starts predefined CLI_SCRIPT (environment variable)
``/opt/docker/bin/entrypoint.d/noop.sh``              Starts a noop endless loop at startup
``/opt/docker/bin/entrypoint.d/root.sh``              Starts a root shell (deprecated)
``/opt/docker/bin/entrypoint.d/supervisord.sh``       Starts supervisord daemon at startup (``CMD ["supervisord"]``)
====================================================  ==================================================================



Supervisord
-----------

Supervisord is a lightweight daemon which starts and monitor other programs. We're using it for running more than one
task in a docker container (eg. PHP-FPM and Apache/Nginx).

====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/opt/docker/etc/supervisor.conf``                   Main supervisord configuration file
``/opt/docker/etc/supervisor.d/*.conf``               Modular service configuration files for supervisord
                                                      (will be included automatically)
``/opt/docker/bin/servide.d/*.sh``                    Service scripts if services needs more than just a single command
                                                      line for startup
====================================================  ==================================================================

Provisioning
------------

With Ansible the provisioning tasks can be easliy done inside the Docker image (eg. for configurations and deployments).
There is also a small provision script for registring and running Ansible roles.

For even simpler tasks there is also a possibility to upload small shell scripts which will be executed at the specific
tags.

Register a new role (eg. with tag build): ``/opt/docker/bin/provision add --tag build rolename``

Run all registred roles and scripts (in Dockerfile): ``/opt/docker/bin/bootstrap.sh``


====================================================  ==================================================================
Tag                                                   Description
----------------------------------------------------  ------------------------------------------------------------------
``bootstrap``                                         Run on Docker image creation (only run once)
``build``                                             Run on Docker image build
``onbuild``                                           Run on Docker image ONBUILD
``entrypoint``                                        Run on Docker image entrypoint execution
                                                      (only here Environment variables are available)
====================================================  ==================================================================

====================================================  ==================================================================
File/Directory                                        Description
----------------------------------------------------  ------------------------------------------------------------------
``/opt/docker/bin/provision``                         Provision script
``/opt/docker/bin/bootstrap.sh``                      Wrapper for running registred provisions
                                                      (just run it as last script in Dockerfile)
``/opt/docker/provision/roles``                       Directory for Ansible roles
``/opt/docker/provision/bootstrap.d/*.sh``            Directory for provisioning shell scripts (tag: bootstrap)
``/opt/docker/provision/build.d/*.sh``                Directory for provisioning shell scripts (tag: build)
``/opt/docker/provision/onbuild.d/*.sh``              Directory for provisioning shell scripts (tag: onbuild)
``/opt/docker/provision/entrypoint.d/*.sh``           Directory for provisioning shell scripts (tag: entrypoint)
====================================================  ==================================================================
