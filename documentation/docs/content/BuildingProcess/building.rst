========
Building
========

Requirements
------------

* **python** and **PIP**
* **coreutils** and **gnu-sed** on OSX: ``brew install coreutils gnu-sed``

How to install dependancies?
----------------------------

The building process, need a lot of python packages ! To install, you must run this command :

.. code-block:: bash

    sudo make setup

This command use **pip** to install packages listed in **requirements.txt**

Building the Dockerfiles
------------------------

For building the Docker images locally you can use ``make all`` to start the build process.
There are multiple targets inside the Makefile for building only specific images eg. ``make webdevops/apache``
