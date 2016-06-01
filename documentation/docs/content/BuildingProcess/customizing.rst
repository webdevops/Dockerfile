===========
Customizing
===========

Baselayout
----------

The ``baselayout`` directory contains a bunch of smaller scripts which will uploaded as tar file into
``webdevops/bootstrap``.

``make baselayout`` will build these tar files and deploy them to the Dockerfile directories.

Provision
---------

The ``provision/`` directory containers files, scripts and provision roles which are copied to the specific Dockerfile
directories.

The rules which directory is processed in which order is specified in ``bin/provision.sh``.

``make provision`` will build these files and deploy them to the Dockerfile directories.


Dockerfile.jinja2 and templates
-------------------------------

All ``Dockerfiles`` are generated from their appropriate ``Dockerfile.jinja2`` files. Inside the jinja2 Dockerfile
templates there are macros which are defined inside ``template/`` directory.

The script ``bin/buildDockerfile.py`` will search for ``Dockerfile.jinja2`` files, processes them and stores them as
``Dockerfile``.

``make provision`` will build these files and deploy them to the Dockerfile directories.
