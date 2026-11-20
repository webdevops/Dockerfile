#!/usr/bin/env bash
# The Blackfire agent, which the Blackfire CLI contains ("blackfire agent:start"). 10-php-debugger.sh enables it with
# PHP_DEBUGGER=blackfire when BLACKFIRE_SERVER_ID and BLACKFIRE_SERVER_TOKEN are set. SERVICE_BLACKFIRE_AGENT_OPTS holds
# further options of "blackfire agent:start", e.g. --socket=tcp://0.0.0.0:8307.

# Init vars
if [[ -z "$SERVICE_BLACKFIRE_AGENT_OPTS" ]]; then SERVICE_BLACKFIRE_AGENT_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/blackfire-agent.d/"

BLACKFIRE_ARGS=()

# blackfire.server_id
if [[ -n "${BLACKFIRE_SERVER_ID+x}" ]]; then
    BLACKFIRE_ARGS+=("--server-id=${BLACKFIRE_SERVER_ID}")
fi

# blackfire.server_token
if [[ -n "${BLACKFIRE_SERVER_TOKEN+x}" ]]; then
    BLACKFIRE_ARGS+=("--server-token=${BLACKFIRE_SERVER_TOKEN}")
fi

# the agent does not start without its configuration file, even if the options above configure it
if [[ ! -e /etc/blackfire/agent ]]; then
    mkdir -p /etc/blackfire
    : > /etc/blackfire/agent
fi

# create directory for unix socket
mkdir -p /var/run/blackfire

exec blackfire agent:start "${BLACKFIRE_ARGS[@]}" $SERVICE_BLACKFIRE_AGENT_OPTS
