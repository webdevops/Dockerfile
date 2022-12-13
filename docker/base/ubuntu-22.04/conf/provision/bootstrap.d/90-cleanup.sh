#!/usr/bin/env bash

# Remove default cronjobs
rm -f -- \
    /etc/cron.daily/logrotate \
    /etc/cron.daily/apt-compat \
    /etc/cron.daily/dpkg \
    /etc/cron.daily/passwd \
    /etc/cron.daily/0yum-daily.cron \
    /etc/cron.daily/logrotate \
    /etc/cron.hourly/0yum-hourly.cron \
    /etc/periodic/daily/logrotate
