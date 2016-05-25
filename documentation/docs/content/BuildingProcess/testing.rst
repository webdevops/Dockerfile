=======
Testing
=======

Testing is done using serverspec_ and rspec_.

The whole test runner can be executed by running ``make test`` from the root directory. Inside the ``test/`` directory
there is an alternative Makefile for running specific Docker image tests.

The Testrunner ``test/run.sh`` defines which tests are executed with which options or environment.

The tests for Docker images are specified in ``test/spec/docker`` directory.

All collection tests are defined inside ``test/spec/collection`` which are using the smaller tests
from ``test/spec/shared``.

.. _rspec: http://rspec.info/
.. _serverspec: http://serverspec.org/
