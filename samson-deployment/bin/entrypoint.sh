#!/bin/bash

trap 'echo sigterm ; exit' SIGTERM
trap 'echo sigkill ; exit' SIGKILL

#############################
## Fix rights
#############################

# Default rights for /root/.ssh
if [ -d /root/.ssh ]; then
    find /root/.ssh/ -type d -print0 | xargs -0 --no-run-if-empty chmod 700
    find /root/.ssh/ -type f -print0 | xargs -0 --no-run-if-empty chmod 600
fi

# Set executable right for deployment
chmod +x /opt/deployment/deploy

# Set executable rights for scripts
chmod +x /opt/docker/bin/*

#############################
## COMMAND
#############################

case "$1" in

    ## Supervisord (start daemons)
    supervisord)
        ## Start services
        cd /
        exec supervisord -c /opt/docker/conf/supervisord.conf --logfile /dev/null --pidfile /dev/null --user root
        ;;

    *)
        exec "$@"
        ;;
esac
