#!/usr/bin/env bash

echo "Installing ionCube loader"

DOWNLOAD_URL="http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz"
(uname -a | grep -q arm64) && DOWNLOAD_URL="https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_aarch64.tar.gz"
TMP_FILE="/tmp/ioncube_loaders.tar.gz"

echo "Downloading from ${DOWNLOAD_URL} ..."
curl -sS ${DOWNLOAD_URL} -o ${TMP_FILE}
echo "Unpacking ..."
tar -xzf ${TMP_FILE} -C /tmp

PHP_VERSION=`php -v | head -1 | grep -o 'PHP [0-9].[0-9]' | sed -r 's/PHP //g'`
PHP_EXTENSION_DIR=`php -i | grep -o -m 1 'extension_dir .* =' | sed -r 's/extension_dir => //g' | sed -r 's/ =//g'`
MOD_INI="${PHP_MOD_INI_DIR}/00-ioncube.ini"
SO_FILE="${PHP_EXTENSION_DIR}/ioncube_loader_lin_${PHP_VERSION}.so"

echo "PHP-VERSION: ${PHP_VERSION}"
echo "PHP-EXTENSION-DIR: ${PHP_EXTENSION_DIR}"
if [[ ! -f "/tmp/ioncube/ioncube_loader_lin_${PHP_VERSION}.so" ]]; then
    echo "There is no ioncube available for PHP${PHP_VERSION}, skipping installation"
else
    echo "Installing ${SO_FILE}"
    cp "/tmp/ioncube/ioncube_loader_lin_${PHP_VERSION}.so" ${SO_FILE}

    echo "Writing module ini"
    echo "[ioncube]" > ${MOD_INI}
    echo "zend_extension = ${SO_FILE}" >> ${MOD_INI}
    echo "; priority=01" >> ${MOD_INI}

    echo "Cleaning up"
    rm -rf $TMP_FILE
    rm -rf /tmp/ioncube

    echo "Enabling ionCube PHP module"
    case "$IMAGE_FAMILY" in
        Debian|Ubuntu)
            # Enable ionCube (if available)
            if [[ -f "${PHP_ETC_DIR}/mods-available/00-ioncube.ini" ]]; then
                ln -sf "${PHP_ETC_DIR}/mods-available/00-ioncube.ini" "${PHP_ETC_DIR}/cli/conf.d/00-ioncube.ini"
                ln -sf "${PHP_ETC_DIR}/mods-available/00-ioncube.ini" "${PHP_ETC_DIR}/fpm/conf.d/00-ioncube.ini"
            fi
        ;;
    esac
fi
