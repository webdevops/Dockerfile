Logstash customization
^^^^^^^^^^^^^^^^^^^^^^

This image has one directory for configuration files which will be automatic loaded.

For configuration filter/ Input / Output the directory ``/usr/local/logstash/logstash.d`` can be used.

Any ``*.conf`` files inside this direcory will be included.

By default, we are add this files :

- 10-inputs.conf

.. code-block:: yaml
    :linenos:

    input {
        tcp {
            port => 10514
            type => syslog
        }

        udp {
            port => 10514
            type => syslog
        }

        gelf {
            port => 12201
            type => docker
        }
    }

- 50-filters.conf

.. code-block:: yaml
    :linenos:

    filter {
            if [type] == "syslog" {
                grok {
                    match => { "message" => "%{SYSLOGTIMESTAMP:syslog_timestamp} %{SYSLOGHOST:syslog_hostname} %{DATA:syslog_program}(?:\[%{POSINT:syslog_pid}\])?: %{GREEDYDATA:syslog_message}" }
                add_field => [ "received_at", "%{@timestamp}" ]
                add_field => [ "received_from", "%{host}" ]
            }

            date {
                match => [ "syslog_timestamp", "MMM  d HH:mm:ss", "MMM dd HH:mm:ss" ]
            }
        }
    }

- 99-outputs.conf

.. code-block:: yaml
    :linenos:

    output {
        stdout { codec => json }
    }

Included plugins:
^^^^^^^^^^^^^^^^^

.. glossary::

    Filter Plugins

        - `aggregate <https://www.elastic.co/guide/en/logstash/current/plugins-filters-aggregate.html>`_
        - `alter <https://www.elastic.co/guide/en/logstash/current/plugins-filters-alter.html>`_
        - `prune <https://www.elastic.co/guide/en/logstash/current/plugins-filters-prune.html>`_

    Output Plugins

        - `redmine <https://www.elastic.co/guide/en/logstash/current/plugins-outputs-redmine.html>`_
        - `influxdb <https://www.elastic.co/guide/en/logstash/current/plugins-outputs-influxdb.html>`_
        - `mongodb <https://www.elastic.co/guide/en/logstash/current/plugins-outputs-mongodb.html>`_


