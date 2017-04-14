#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/ansible:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:centos-7

# Install ansible
RUN /usr/local/bin/yum-install \
        epel-release \
        PyYAML \
        python-jinja2 \
        python-httplib2 \
        python-keyczar \
        python-paramiko \
        python-setuptools \
        python-setuptools-devel \
        libffi \
        python-devel \
        libffi-devel \
    && easy_install pip \
    && pip install --upgrade pip \
    && hash -r \
    && pip install --no-cache-dir ansible \
    && chmod 750 /usr/bin/ansible* \
    # Cleanup
    && yum erase -y python-devel \
    && docker-image-cleanup
