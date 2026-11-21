# The cron jobs of the CRONJOB_* variables, see docs/customization.md#cron-jobs. A container that starts supervisord
# also enables cron for them where it is not enabled.
if [[ "$TASK" == "supervisord" ]]; then
    docker-cronjob --environment --enable-cron
else
    docker-cronjob --environment
fi
