#!/usr/bin/env bash

exec bundle exec rake spec["$1","$2","$3"]

