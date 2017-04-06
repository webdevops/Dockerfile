#!/usr/bin/env bash

#############################################
## Configure and run liquibase
#############################################

if [[ -n "$LIQUIDSOAP_TEMPLATE" ]]; then
    go-replace --mode=template -s Foobar -r Foobar -- "$LIQUIDSOAP_TEMPLATE"
fi

exec gosu "$LIQUIDSOAP_USER" liquidsoap "$LIQUIDSOAP_SCRIPT"
