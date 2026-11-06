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

## PHP 8.6: removed extensions

The PHP 8.6 images no longer contain the `vips` and `xmlrpc` extensions. Both are
abandoned upstream and do not build against PHP 8.6. They remain available in the
PHP 8.2–8.5 images.

* **vips:** use [`jcupitt/vips`](https://github.com/libvips/php-vips) 2.x, which
  talks to libvips through FFI. `libvips` and the `ffi` extension are still part of
  the images. FFI is limited to preloading by default; to use it in PHP-FPM, enable it
  when starting the container:

  ```yaml
  services:
    app:
      image: webdevops/php:8.6
      environment:
        php.ffi.enable: "true"
  ```

* **xmlrpc:** use a userland XML-RPC library, for example
  [`phpxmlrpc/phpxmlrpc`](https://github.com/gggeek/phpxmlrpc).
