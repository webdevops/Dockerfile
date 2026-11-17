#!/usr/bin/env bash

if [ $IMAGE_FAMILY == "Alpine" ]; then
    echo "Skipping ionCube installation on Alpine"
    return
fi

echo "Installing ionCube loader"

case "$(uname -m)" in
    x86_64) IONCUBE_ARCH="x86-64" ;;
    aarch64) IONCUBE_ARCH="aarch64" ;;
    *)
        echo "Skipping ionCube installation on unsupported architecture $(uname -m)"
        return
    ;;
esac

DOWNLOAD_URL="https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_${IONCUBE_ARCH}.tar.gz"
TMP_FILE="/tmp/ioncube_loaders.tar.gz"

echo "Downloading ..."
curl -fsS "${DOWNLOAD_URL}" -o "${TMP_FILE}"
echo "Unpacking ..."
tar -xzf ${TMP_FILE} -C /tmp

PHP_VERSION="$(php -n -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
PHP_EXTENSION_DIR="$(php -n -r 'echo ini_get("extension_dir");')"
MOD_INI="${PHP_MOD_INI_DIR}/00-ioncube.ini"
SO_FILE="${PHP_EXTENSION_DIR}/ioncube_loader_lin_${PHP_VERSION}.so"

echo "PHP-VERSION: ${PHP_VERSION}"
echo "PHP-EXTENSION-DIR: ${PHP_EXTENSION_DIR}"
if [[ ! -f "/tmp/ioncube/ioncube_loader_lin_${PHP_VERSION}.so" ]]; then
    echo "There is no ioncube available for PHP${PHP_VERSION}, skipping installation"
else
    echo "Installing ${SO_FILE}"
    cp "/tmp/ioncube/ioncube_loader_lin_${PHP_VERSION}.so" ${SO_FILE}

    echo "Checking ionCube loader"
    php -n -d "zend_extension=${SO_FILE}" -r 'exit(extension_loaded("ionCube Loader") ? 0 : 1);' || return 1

    echo "Writing module ini"
    echo "[ioncube]" > ${MOD_INI}
    echo "zend_extension = ${SO_FILE}" >> ${MOD_INI}
    echo "; priority=01" >> ${MOD_INI}

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

echo "Cleaning up"
rm -rf "$TMP_FILE" /tmp/ioncube
