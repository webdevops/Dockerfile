#!/usr/bin/env bash

if [[ -n "$VARNISH_CONFIG" ]]; then
    echo " Using CUSTOM varnish configuration file"

    if [[ ! -f "$VARNISH_CONFIG" ]]; then
        echo "[ERROR] Varnish configuration file '${VARNISH_CONFIG}' not found"
        exit 1
    fi
else
    echo "Using DEFAULT varnish configuration"

    VARNISH_CONFIG="/opt/docker/etc/varnish/varnish.vcl"

    if [[ -z "$VARNISH_BACKEND_HOST" ]]; then
        echo "[ERROR] No varnish backend host set (VARNISH_BACKEND_HOST)"
        exit 1
    fi

    if [[ -z "$VARNISH_BACKEND_PORT" ]]; then
        echo "[ERROR] No varnish backend port set (VARNISH_BACKEND_PORT)"
        exit 1
    fi
fi

if [[ -z "$VARNISH_STORAGE" ]]; then
    "[ERROR] No varnish storage definition set (VARNISH_STORAGE)"
    exit 1
fi

if [[ -z "$VARNISH_PORT" ]]; then
    "[ERROR] No varnish listen port set (VARNISH_PORT)"
    exit 1
fi

go-replace \
    -s "<VARNISH_BACKEND_HOST>" -r "$VARNISH_BACKEND_HOST" \
    -s "<VARNISH_BACKEND_PORT>" -r "$VARNISH_BACKEND_PORT" \
    -- "$VARNISH_CONFIG"

echo " Starting varnishd..."
echo "     listening on: 0.0.0.0:${VARNISH_PORT}"
echo "      config file: ${VARNISH_CONFIG}"
echo "          backend: ${VARNISH_BACKEND_HOST}:${VARNISH_BACKEND_PORT}"
echo "          storage: ${VARNISH_STORAGE}"
echo "    varnishd opts: ${VARNISH_OPTS}"
echo ""

exec varnishd -j unix,user=varnish -F \
    -a "0.0.0.0:${VARNISH_PORT}" \
    -f "$VARNISH_CONFIG" \
    -s "$VARNISH_STORAGE" \
    $VARNISH_OPTS
