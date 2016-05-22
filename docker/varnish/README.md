# varnish container layout

Automated build and tested by [WebDevOps Build Server](https://build.webdevops.io/)

## Filesystem layout

The whole docker directroy is deployed into `/opt/docker/`.

File                                       | Description
------------------------------------------ | ------------------------------------------------------------------------------
`/opt/docker/bin/entrypoint.d/varnishd.sh` | Entrypoint cmd file for starting varnishd
`/opt/docker/etc/varnish/varnish.vcl`      | Default varnish configuration file (with `VARNISH_BACKEND_HOST` and `VARNISH_BACKEND_PORT` markers)


## Environment variables

Variable               | Description
---------------------- | ------------------------------------------------------------------------------
`VARNISH_PORT`         | Listening port of varnish
`VARNISH_CONFIG`       | Path to custom varnish configuration file (must be uploaded to image)
`VARNISH_STORAGE`      | Storage setting (default: `malloc,128m`)
`VARNISH_OPTS`         | Extra varnishd options
`VARNISH_BACKEND_HOST` | Host of backend server 
`VARNISH_BACKEND_PORT` | Port of backend server (default: `80`)

