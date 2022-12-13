#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

# Installation
case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install syslog-ng syslog-ng-core
        ;;

    RedHat)
        yum-install syslog-ng

        # remove logrotate (not needed for docker)
        rm -f "/etc/cron.daily/logrotate"
        ;;

    Alpine)
        apk-install syslog-ng
        ;;
esac

## Configuration
SYSLOG_NG_VERSION=$(syslog-ng --version | grep -E -e '^Installer-Version:[ ]+[0-9]+\.[0-9]+' | head -n 1 | awk '{print $2}' | cut -f 1,2 -d .)

# Disable caps inside container
if [[ -f /etc/default/syslog-ng ]]; then
    go-replace --mode=lineinfile \
        -s "SYSLOGNG_OPTS" -r "SYSLOGNG_OPTS = --no-caps" \
        -- /etc/default/syslog-ng
fi

# Symlink configuration
ln -s -f /opt/docker/etc/syslog-ng/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

go-replace --mode=lineinfile \
    -s "@version"      -r "@version: ${SYSLOG_NG_VERSION}" \
    -- /etc/syslog-ng/syslog-ng.conf

# Ensure /var/lib/syslog-ng exists
mkdir -p /var/lib/syslog-ng
