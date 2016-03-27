#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:latest
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0

RUN /usr/local/bin/apt-install \
    libjpeg-dev \
    libtiff5-dev \
    libjpeg8-dev \
    zlib1g-dev \
    libfreetype6-dev \
    liblcms2-dev \
    libwebp-dev \
    tcl8.6-dev \
    tk8.6-dev \
    python-tk \
    && pip install --upgrade Pillow \
    && pip install --upgrade pyyaml \
    && pip install --upgrade sphinxcontrib-googlechart \
    && pip install --upgrade sphinxcontrib-googlemaps \
    && pip install --upgrade sphinxcontrib-httpdomain \
    && pip install --upgrade sphinxcontrib-slide \
    && pip install --upgrade sphinx-autobuild \
    && pip install --upgrade recommonmark
