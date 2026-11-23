# Debian: the variables of the container for the jobs that docker-cronjob-shell runs, written anew at every
# start of cron, after 10-init.sh has copied the crontabs of /opt/docker/etc/cron; see docs/customization.md#cron-jobs.
# BusyBox crond on Alpine passes them on by itself.
docker-cronjob --job-environment
