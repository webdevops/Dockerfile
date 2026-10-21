# PHP

## Session upload progress

All PHP image variants disable `session.upload_progress.enabled` by default.
PHP's session-based upload progress can write request-controlled progress data to
the session store while parsing a multipart upload, before application code runs.
Making this feature opt-in reduces the session processing exposed to upload
requests. Keep PHP up to date; this setting does not replace security updates.

Regular file uploads (`$_FILES`) and application sessions continue to work.
Applications that use `PHP_SESSION_UPLOAD_PROGRESS` to populate progress data in
`$_SESSION` will no longer receive those updates. Upload cancellation through
that progress data also requires the feature to be enabled. Browser-side upload
progress, such as `XMLHttpRequest.upload`, does not depend on this setting.

If your application needs PHP's session-based upload progress, enable it when
starting the container:

```yaml
services:
  app:
    image: webdevops/php:8.4
    environment:
      php.session.upload_progress.enabled: "On"
```

For `docker run`, use `-e php.session.upload_progress.enabled=On`. Alternatively,
set `session.upload_progress.enabled = On` in a custom
`/opt/docker/etc/php/php.ini`. Restart the container after changing the setting;
it cannot be enabled with `ini_set()` because PHP parses uploads before running
the application.

