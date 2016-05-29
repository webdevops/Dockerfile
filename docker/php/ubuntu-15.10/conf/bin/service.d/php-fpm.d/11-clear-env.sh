#
# Workaround for old php-fpm versions which don't have clear_env setting
#

VARIABLE_LIST="; Workaround for missing clear_env feature in PHP-FPM"

# For each exported variable
for envVariable in $(printenv|cut -f1 -d=); do

    case "$envVariable" in
        "_"|"PATH"|"PWD")
            ## ignore this variables
            ;;

        *)
                ## get content of variable
                envVariableContent="${!envVariable}"

                if [[ -n "$envVariableContent" ]]; then

                    ## quote quotes
                    envVariableContent=${envVariableContent//\"/\\\"}

                    ## add to list
                    VARIABLE_LIST="${VARIABLE_LIST}"$'\n'"env[${envVariable}] = \"${envVariableContent}\""
                fi
                ;;
    esac

done

find /opt/docker/etc/php/fpm/pool.d/ -iname '*.conf' -print0 | xargs -0 -r rpl --quiet ";#CLEAR_ENV_WORKAROUND#" "$VARIABLE_LIST" > /dev/null

