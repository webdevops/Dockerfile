# Install crontab files
find /opt/docker/etc/cron -type f | while read CRONTAB_FILE; do
    # fix permissions
    chmod 0644 -- "$CRONTAB_FILE"

    # add newline, cron needs this
    echo >> "$CRONTAB_FILE"

    # Install files
    cp -a -- "$CRONTAB_FILE" "/etc/cron.d/$(basename "$CRONTAB_FILE")"
done
