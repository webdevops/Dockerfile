# Image tests: with IMAGE_TEST_CRON set, adds a cron job for the current and the next hour in Berlin before cron
# starts, which marks that it ran, see ContainerEnvironmentChecks. Cron runs it only if it uses the time zone of TZ.
if [[ -n "${IMAGE_TEST_CRON:-}" ]]; then
    hour=$(TZ=Europe/Berlin date +%H)
    hour=${hour#0}
    hours="${hour},$(( (hour + 1) % 24 ))"
    if [[ -d /etc/crontabs ]]; then
        # BusyBox crond on Alpine
        echo "* ${hours} * * * echo ran > /tmp/image-tests/cron-job" >> /etc/crontabs/root
    else
        echo "* ${hours} * * * root echo ran > /tmp/image-tests/cron-job" > /etc/cron.d/image-test
        chmod 644 /etc/cron.d/image-test
    fi
fi
