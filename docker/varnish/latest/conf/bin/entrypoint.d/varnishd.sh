#!/usr/bin/env bash

if [[ -n "$VARNISH_CONFIG" ]]; then
    echo " Using custom varnish configuration file"

    if [[ -f "$VARNISH_CONFIG" ]]; then
        echo "[ERROR] Varnish configuration file '${VARNISH_CONFIG}' not found"
        exit 1
    fi
else
    echo "Using default varnish configuration"

    VARNISH_CONFIG="/opt/docker/etc/varnish/varnish.vcl"

    if [[ -z "$VARNISH_BACKEND_HOST" ]]; then
        echo "[ERROR] Environment variable VARNISH_BACKEND_HOST not set"
        exit 1
    fi

    if [[ -z "$VARNISH_BACKEND_PORT" ]]; then
        echo "[ERROR] Environment variable VARNISH_BACKEND_HOST not set"
        exit 1
    fi
fi

if [[ -z "$VARNISH_STORAGE" ]]; then
    "[ERROR] No varnish storage definition set"
    exit 1
fi


if [[ -n "$VARNISH_BACKEND_HOST" ]]; then
    rpl --quiet "<VARNISH_BACKEND_HOST>" "$VARNISH_BACKEND_HOST" "$VARNISH_CONFIG"
fi

if [[ -n "$VARNISH_BACKEND_PORT" ]]; then
    rpl --quiet "<VARNISH_BACKEND_PORT>" "$VARNISH_BACKEND_PORT" "$VARNISH_CONFIG"
fi

exec varnishd -F \
    -f "$VARNISH_CONFIG" \
    -s "$VARNISH_STORAGE" \
    $VARNISH_OPTS
