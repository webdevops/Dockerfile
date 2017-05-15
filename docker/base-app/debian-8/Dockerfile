#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base-app:debian-8
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:debian-8

ENV APPLICATION_USER=application \
    APPLICATION_GROUP=application \
    APPLICATION_PATH=/app \
    APPLICATION_UID=1000 \
    APPLICATION_GID=1000

COPY conf/ /opt/docker/

# Install services
RUN /usr/local/bin/apt-install \
        # Install common tools
        zip \
        unzip \
        bzip2 \
        moreutils \
        dnsutils \
        openssh-client \
        rsync \
        git \
    && /usr/local/bin/generate-locales \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup
