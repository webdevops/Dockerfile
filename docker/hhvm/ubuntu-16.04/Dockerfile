#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-16.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install hhvm environment
RUN /usr/local/bin/apt-install \
        hhvm \
        imagemagick \
        graphicsmagick \
        ghostscript \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000

