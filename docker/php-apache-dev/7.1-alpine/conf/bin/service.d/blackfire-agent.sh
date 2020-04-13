#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_BLACKFIRE_AGENT_OPTS" ]]; then SERVICE_BLACKFIRE_AGENT_OPTS=""; fi

source /opt/docker/bin/config.sh

BLACKFIRE_ARGS=""

includeScriptDir "/opt/docker/bin/service.d/syslog-ng.d/"

# blackfire.server_id
if [[ -n "${BLACKFIRE_SERVER_ID+x}" ]]; then
    BLACKFIRE_ARGS="$BLACKFIRE_ARGS --server-id=\"${BLACKFIRE_SERVER_ID}\""
fi

# blackfire.server_token
if [[ -n "${BLACKFIRE_SERVER_TOKEN+x}" ]]; then
    BLACKFIRE_ARGS="$BLACKFIRE_ARGS --server-token=\"${BLACKFIRE_SERVER_TOKEN}\""
fi

# create directory for unix socket
mkdir -p /var/run/blackfire

eval exec blackfire-agent $BLACKFIRE_ARGS $SERVICE_BLACKFIRE_AGENT_OPTS
