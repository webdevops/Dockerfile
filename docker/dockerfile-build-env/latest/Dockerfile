#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/dockerfile-build-env:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:ubuntu-16.04

RUN apt-install \
        build-essential \
        python \
        python-dev \
        python-setuptools \
        python-pip \
        ruby \
        ruby-dev \
        ruby-bundler \
    && pip install --upgrade pip \
    && pip install --upgrade setuptools \
    && pip install --upgrade \
        MarkupSafe \
        Jinja2 \
        graphviz \
        funcsigs \
        pbr \
        six \
        mock \
        psutil \
        pylev \
        py \
        appdirs \
        pyparsing \
        packaging \
        pytest \
        simplejson \
        backpack \
        cleo \
        pyyaml \
        yamlordereddictloader \
        testinfra \
        cloudpickle \
        pyinotify \
        configparser \
        doit==0.29.0 \
        termcolor \
        pytest-timeout \
        pytest-rerunfailures \
    && docker-image-cleanup
