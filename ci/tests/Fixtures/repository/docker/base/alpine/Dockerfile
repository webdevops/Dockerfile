#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM alpine:3.22

COPY --from=webdevops/toolbox:latest /baselayout/sbin/* /sbin/

# Installs a package by version

RUN set -x \
    # Install the packages
    && apk-install \
        two \
    && docker-image-cleanup

RUN set -x \
    # Install the packages
    && apk-install \
        other \
    && docker-image-cleanup
