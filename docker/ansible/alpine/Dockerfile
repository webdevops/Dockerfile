#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/ansible:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine

# Install ansible
RUN /usr/local/bin/apk-install \
        python \
        python-dev \
        py-setuptools \
        py-crypto \
        py2-pip \
        py-cparser \
        py-cryptography \
        py-markupsafe \
        py-cffi \
        py-yaml \
        py-jinja2 \
        py-paramiko \
    && pip install --upgrade pip \
    && hash -r \
    && pip install --no-cache-dir ansible \
    && chmod 750 /usr/bin/ansible* \
    # Cleanup
    && apk del python-dev \
    && docker-image-cleanup
