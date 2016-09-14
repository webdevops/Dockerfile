#!/usr/bin/env bash

set -e

source /opt/docker/bin/config.sh

exec /usr/local/logstash/bin/logstash -f /usr/local/logstash/logstash.d --auto-reload --reload-interval 15