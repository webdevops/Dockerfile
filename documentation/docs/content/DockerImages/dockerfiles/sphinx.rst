================
webdevops/sphinx
================

*deprecated*

These image extends ``webdevops/bootstrap`` and provides a sphinx build system.

Docker image tags
-----------------

====================== ==========================
Tag                    Distribution name
====================== ==========================
``latest``             Alpine 3
====================== ==========================


Usage
-----

.. code-block:: bash

    # Build and watches documentation in ./documentation/docs/
    docker run -t -i --rm -p 8080:8000 -v "$(pwd)/documentation/docs/:/opt/docs" webdevops/sphinx sphinx-autobuild --poll -H 0.0.0.0 /opt/docs html
