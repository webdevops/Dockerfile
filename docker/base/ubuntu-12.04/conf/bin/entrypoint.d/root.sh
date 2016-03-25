#!/usr/bin/env bash

#############################################
## Root shell
#############################################

if [ "$#" -eq 1 ]; then
    ## No command, fall back to interactive shell
    exec bash
else
    ## Exec root command
    shift
    exec "$@"
fi
