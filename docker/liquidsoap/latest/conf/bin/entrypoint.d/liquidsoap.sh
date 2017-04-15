#!/usr/bin/env bash

#############################################
## Configure and run liquidsoap
#############################################

if [[ -n "$LIQUIDSOAP_TEMPLATE" ]]; then
    go-replace --mode=template -- "$LIQUIDSOAP_TEMPLATE"
fi

exec gosu "$LIQUIDSOAP_USER" liquidsoap "$LIQUIDSOAP_SCRIPT"
