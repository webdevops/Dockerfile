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

                ## php-fpm requires that env variable has to be filled with content
                if [[ -n "$envVariableContent" ]]; then
                    ## quote quotes
                    envVariableContent=${envVariableContent//\"/\\\"}

                    ## add to list
                    VARIABLE_LIST="${VARIABLE_LIST}"$'\n'"env[${envVariable}] = \"${envVariableContent}\""
                fi
                ;;
    esac

done

# Replace ;#CLEAR_ENV_WORKAROUND# with environment variable list for all php-fpm pool files
go-replace \
    -s ";#CLEAR_ENV_WORKAROUND#" -r "$VARIABLE_LIST" \
    --path=/opt/docker/etc/php/fpm/pool.d/ \
    --path-pattern='*.conf' \
    --ignore-empty

