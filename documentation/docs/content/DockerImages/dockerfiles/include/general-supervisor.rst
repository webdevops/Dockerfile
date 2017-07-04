**Uses Supervisord**

This image is using supervisor and runs the daemon under user ``application`` (UID 1000; GID 1000) as default. If the container is
started under a different user the daemon will be run under the specified uid.
