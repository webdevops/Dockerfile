======================
Commands (bin/console)
======================

Requirements
------------

* **python** and **PIP**
* **ruby** for serverspec tests

Install dependencies
--------------------

The building process, we need some python packages as well as ruby rspec and serverspec packages:

.. code-block:: bash

    make requirements

Configuration
-------------

All commands are using configuration options from ``conf/console.yml``.

bin/console tasks
-----------------

Tip: Most tasks are using arguments as whitelist addon for easier usage.


bin/console docker:build
~~~~~~~~~~~~~~~~~~~~~~~~

Build all Dockerfiles found in ``docker/`` directory. The directory structure defines the naming of the built images
(using convention over configuration).

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
-v                                     Verbose output                                                                    *option only*
--threads=n                            Run in parallized mode (currently multi-process instead of real threads)          *numeric values*, auto, auto/2, auto*2, auto-2, auto+2
--dry-run                              Don't really execute build process                                                *option only*
--no-cache                             Don't use Docker caching                                                          *option only*
--retry=n                              Retry process multiple times (eg. for networking issues)                          *numeric values*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================

bin/console docker:push
~~~~~~~~~~~~~~~~~~~~~~~

Push (upload) all built Docker images to registry (using convention over configuration).

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
-v                                     Verbose output                                                                    *option only*
--threads=n                            Run in parallized mode (currently multi-process instead of real threads)          *numeric values*, auto, auto/2, auto*2, auto-2, auto+2
--dry-run                              Don't really execute build process                                                *option only*
--retry=n                              Retry process multiple times (eg. for networking issues)                          *numeric values*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================

bin/console docker:pull
~~~~~~~~~~~~~~~~~~~~~~~

Pull (download) all built Docker images to registry (using convention over configuration).

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
-v                                     Verbose output                                                                    *option only*
--threads=n                            Run in parallized mode (currently multi-process instead of real threads)          *numeric values*, auto, auto/2, auto*2, auto-2, auto+2
--dry-run                              Don't really execute build process                                                *option only*
--retry=n                              Retry process multiple times (eg. for networking issues)                          *numeric values*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================

bin/console docker:exec
~~~~~~~~~~~~~~~~~~~~~~~

Execute argument as command inside all docker images.

eg. ``bin/console docker:exec --whitelist php -- 'php -v'``

Tip: Separate the docker image command arguments from the console commands with two dashes.

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
-v                                     Verbose output                                                                    *option only*
--dry-run                              Don't really execute build process                                                *option only*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================


bin/console test:testinfra
~~~~~~~~~~~~~~~~~~~~~~~~~~

Test built images with testinfra_ (python module), spec files are inside ``tests/testinfra``

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
-v                                     Verbose output                                                                    *option only*
--threads=n                            Run in parallized mode (currently multi-process instead of real threads)          *numeric values*, auto, auto/2, auto*2, auto-2, auto+2
--dry-run                              Don't really execute build process                                                *option only*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================

bin/console test:serverspec
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Test built images with serverspec_ (python module), spec files are inside ``tests/serverspec``

=====================================  ================================================================================= ===============================================
Option                                 Description                                                                       Values
=====================================  ================================================================================= ===============================================
--threads=n                            Run in parallized mode (currently multi-process instead of real threads)          *numeric values*, auto, auto/2, auto*2, auto-2, auto+2
--dry-run                              Don't really execute build process                                                *option only*
--retry=n                              Retry process multiple times (eg. for networking issues)                          *numeric values*
--whitelist=term                       Only build Docker images with *term* in name                                      *string value*
--blacklist=term                       Don't build Docker images with *term* in name                                     *string value*
=====================================  ================================================================================= ===============================================

bin/console generate:graph
~~~~~~~~~~~~~~~~~~~~~~~~~~

Generates Docker images dependency graph using graphviz.

bin/console generate:dockerfile
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Generate Dockerfiles from ``Dockerfile.jinja2`` templates.

Configuration is stored inside ``conf/diagram.yml``.

bin/console generate:provision
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Generate provision (common configuration files) and deploy them to the specified Dockerfile directories.

Configuration is stored inside ``conf/provision.yml``.


.. _testinfra: https://github.com/philpep/testinfra
.. _serverspec: http://serverspec.org/
