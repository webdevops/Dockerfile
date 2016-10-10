===================
webdevops/liquibase
===================

The certbot images are based on ``java`` with liquibase and mysql driver

Docker image tags
-----------------

====================== ==========================
Tag                    Distribution name
====================== ==========================
``latest``             Based on official java
====================== ==========================

Environment variables
---------------------

========================== ============================ ==============================================
Environment variable       Description                  Default
========================== ============================ ==============================================
``LIQUIBASE_VERSION``      Installed Liquibase version  *not changeable*
``LIQUIBASE_DRIVER``       Database driver              ``com.mysql.jdbc.Driver``
``LIQUIBASE_CLASSPATH``    Java class path              ``/usr/share/java/mysql.jar``
``LIQUIBASE_URL``          DB url                       *empty* (eg. ``jdbc:mysql://host/app``)
``LIQUIBASE_USERNAME``     DB username                  *empty*
``LIQUIBASE_PASSWORD``     DB password                  *empty*
``LIQUIBASE_CHANGELOG``    Changelog file               ``/liquibase/changelog.xml``
``LIQUIBASE_CONTEXTS``     Server contexts              *empty*
``LIQUIBASE_OPTS``         Additional options           *empty*
========================== ============================ ==============================================

