#################################################
# PHP debugger environment variables
#
# Appended to php.webdevops.ini (98-webdevops.ini) after 20-php.sh wrote it anew, so that they replace the image's
# defaults; your own php.ini is loaded after it and wins.
#################################################

function phpEnvironmentVariable() {
    PHP_INI_KEY="$1"
    PHP_ENV_NAME="$2"

    if [[ -n "${!PHP_ENV_NAME+x}" ]]; then
        PHP_ENV_VALUE="${!PHP_ENV_NAME}"
        echo "${PHP_INI_KEY}=\"${PHP_ENV_VALUE}\"" >> /opt/docker/etc/php/php.webdevops.ini
    fi
}

###################
# XDEBUG
###################

# xdebug3 remote debugger
phpEnvironmentVariable "xdebug.discover_client_host" "XDEBUG_DISCOVER_CLIENT_HOST"
phpEnvironmentVariable "xdebug.mode"                 "XDEBUG_MODE"
phpEnvironmentVariable "xdebug.start_with_request"   "XDEBUG_START_WITH_REQUEST"
phpEnvironmentVariable "xdebug.client_host"          "XDEBUG_CLIENT_HOST"
phpEnvironmentVariable "xdebug.client_port"          "XDEBUG_CLIENT_PORT"

# xdebug3 profiler
phpEnvironmentVariable "xdebug.trigger_value"       "XDEBUG_TRIGGER_VALUE"
phpEnvironmentVariable "xdebug.output_dir"          "XDEBUG_OUTPUT_DIR"

###################
# BLACKFIRE
###################
phpEnvironmentVariable "blackfire.server_id"    "BLACKFIRE_SERVER_ID"
phpEnvironmentVariable "blackfire.server_token" "BLACKFIRE_SERVER_TOKEN"
