#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/dockerfile-build-env:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-16.04

RUN apt-install \
        build-essential \
        apt-transport-https \
        ca-certificates \
        software-properties-common \
        python \
        python-dev \
        python-setuptools \
        python-pip \
        graphviz \
        ruby \
        ruby-dev \
        ruby-bundler \
    && curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - \
    && add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu    $(lsb_release -cs)    stable" \
    && apt-install docker-ce \
    && usermod -aG docker application \
    && pip install --upgrade pip \
    && hash -r pip \
    && pip install --upgrade setuptools \
    && git clone --depth 1 https://github.com/webdevops/Dockerfile.git /tmp/Dockerfile \
    && cd /tmp/Dockerfile \
    && make setup \
    && docker-image-cleanup

WORKDIR /app

CMD ["gosu", "application", "bash"]
