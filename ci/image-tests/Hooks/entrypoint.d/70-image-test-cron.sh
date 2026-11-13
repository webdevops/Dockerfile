# Image tests: with IMAGE_TEST_CRON set to hours, e.g. "9,10", adds a cron job for these hours before cron starts, which
# marks that it ran, see ContainerEnvironmentChecks. The tests set the current and the next hour in Berlin, worked out
# outside the image, so cron runs the job only if it uses the time zone of TZ.
if [[ -n "${IMAGE_TEST_CRON:-}" ]]; then
    if [[ -d /etc/crontabs ]]; then
        # BusyBox crond on Alpine
        echo "* ${IMAGE_TEST_CRON} * * * echo ran > /tmp/image-tests/cron-job" >> /etc/crontabs/root
    else
        echo "* ${IMAGE_TEST_CRON} * * * root echo ran > /tmp/image-tests/cron-job" > /etc/cron.d/image-test
        chmod 644 /etc/cron.d/image-test
    fi
fi
