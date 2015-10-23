#!/usr/bin/env bash

mkdir -p "${FTP_PATH}"

if ( id "${FTP_USER}" ); then
  echo "User ${FTP_USER} already exists"
else
  echo "Creating user and group ${FTP_USER}"
  ENC_PASS=$(perl -e 'print crypt($ARGV[0], "password")' "${FTP_PASSWORD}")
  groupadd -g "${FTP_GID}" "${FTP_USER}"
  useradd -d "${FTP_PATH}" -m -p "${ENC_PASS}" -u "${FTP_UID}" --gid "${FTP_USER}" -s /bin/sh "${FTP_USER}"
fi
