# Image tests: with IMAGE_TEST_CRON set to hours, e.g. "9,10", adds a cron job for these hours before cron starts, which
# marks that it ran, see ContainerEnvironmentChecks. The tests set the current and the next hour in Berlin, worked out
# outside the image, so cron runs the job only if it uses the time zone of TZ.
# It also adds a job with docker-cronjob that writes a line to stdout and one to stderr every minute, in upper case, so
# that the lines differ from the command, which cron logs.
if [[ -n "${IMAGE_TEST_CRON:-}" ]]; then
    if [[ -d /etc/crontabs ]]; then
        # BusyBox crond on Alpine
        echo "* ${IMAGE_TEST_CRON} * * * echo ran > /tmp/image-tests/cron-job" >> /etc/crontabs/root
    else
        echo "* ${IMAGE_TEST_CRON} * * * root echo ran > /tmp/image-tests/cron-job" > /etc/cron.d/image-test
        chmod 644 /etc/cron.d/image-test
    fi

    # where docker-cronjob fails, as on Alpine before it wrote to /etc/crontabs, the container starts all the same; the
    # test of the output fails then
    if id application > /dev/null 2>&1; then
        docker-cronjob '* * * * * application echo image-test-cron-output | tr a-z A-Z; echo image-test-cron-error | tr a-z A-Z >&2' || true
    fi
fi
