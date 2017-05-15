#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

case "$IMAGE_FAMILY" in
    Alpine)
        mkdir -p /etc/periodic.d
        ln -s /etc/cron.hourly  /etc/periodic/hourly
        ln -s /etc/cron.daily   /etc/periodic/daily
        ln -s /etc/cron.weekly  /etc/periodic/weekly
        ln -s /etc/cron.monthly /etc/periodic/monthly
        ln -s /etc/cron.d       /etc/periodic.d
        ;;

    Debian|Ubuntu)
        cat << 'EOF' > /etc/crontab
# /etc/crontab: system-wide crontab
# Unlike any other crontab you don't have to run the `crontab'
# command to install the new version when you edit this file
# and files in /etc/cron.d. These files also have username fields,
# that none of the other crontabs do.

SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# m h dom mon dow user  command
17 *    * * *   root    cd / && run-parts --report /etc/cron.hourly
25 6    * * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )
47 6    * * 7   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.weekly )
52 6    1 * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.monthly )
EOF

        mkdir -p \
            /etc/cron.d/ \
            /etc/cron.daily/ \
            /etc/cron.hourly/ \
            /etc/cron.monthly/ \
            /etc/cron.weekly/
        ;;

    RedHat)
        cat << 'EOF' > /etc/crontab
SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=root

# For details see man 4 crontabs

# Example of job definition:
# .---------------- minute (0 - 59)
# |  .------------- hour (0 - 23)
# |  |  .---------- day of month (1 - 31)
# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
# |  |  |  |  |
# *  *  *  *  * user    command to be executed
17 *    * * *   root    cd / && run-parts --report /etc/cron.hourly
25 6    * * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )
47 6    * * 7   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.weekly )
52 6    1 * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.monthly )
EOF

        mkdir -p \
            /etc/cron.d/ \
            /etc/cron.daily/ \
            /etc/cron.hourly/ \
            /etc/cron.monthly/ \
            /etc/cron.weekly/
        ;;
esac
