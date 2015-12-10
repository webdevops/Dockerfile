#!/usr/bin/env bash
set -e

# Apache gets grumpy about PID files pre-existing
rm -f /var/run/httpd/httpd.pid

exec httpd -DFOREGROUND