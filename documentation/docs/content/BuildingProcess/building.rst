========
Building
========

Requirements
------------

* python
* coreutils and gnu-sed on OSX: ``brew install coreutils gnu-sed``


Building the Dockerfiles
------------------------

For building the Docker images locally you can use ``make all`` to start the build process.
There are multiple targets inside the Makefile for building only specific images eg. ``make webdevops/apache``
