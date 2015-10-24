#!/usr/bin/env bash

groupadd --gid "${APPLICATION_GID}" "${APPLICATION_GROUP}"
useradd --home /home/application --gid "${APPLICATION_GID}" --shell /bin/bash --uid "${APPLICATION_UID}" "${APPLICATION_USER}"

chown -R "${APPLICATION_USER}:${APPLICATION_GROUP}" /home/application