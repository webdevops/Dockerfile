#!/usr/bin/env bash

CURRENT_APPLICATION_UID=$(id -u application)
CURRENT_APPLICATION_GID=$(id -g application)

# APPLICATION_GID changed?
if [ "${APPLICATION_GID}" -ne "${CURRENT_APPLICATION_GID}" ]; then
    echo "Detected APPLICATION_GID (${CURRENT_APPLICATION_GID} => ${APPLICATION_GID}) change"
    groupmod -g "${APPLICATION_GID}" "${APPLICATION_GROUP}"
fi

# APPLICATION_UID changed?
if [ "${APPLICATION_UID}" -ne "${CURRENT_APPLICATION_UID}" ]; then
    echo "Detected APPLICATION_UID (${CURRENT_APPLICATION_UID} => ${APPLICATION_UID}) change"
    usermod -u "${APPLICATION_UID}" "${APPLICATION_USER}"
fi

# Fix rights of home directory
if [ "${APPLICATION_UID}" -ne "${CURRENT_APPLICATION_UID}" -o "${APPLICATION_GID}" -ne "${CURRENT_APPLICATION_GID}" ]; then
    chown -R "${APPLICATION_USER}:${APPLICATION_GROUP}" /home/application
fi