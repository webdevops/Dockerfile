#++++++++++++++++++++++++++++++++++++++
# Sphinx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.22.0

RUN /usr/local/bin/apk-install \
    py-pillow \
    py-yaml \
    py-sphinx \
    py-sphinx_rtd_theme \
    && pip install --upgrade sphinxcontrib-googlechart \
    && pip install --upgrade sphinxcontrib-googlemaps \
    && pip install --upgrade sphinxcontrib-httpdomain \
    && pip install --upgrade sphinxcontrib-slide \
    && pip install --upgrade sphinx-autobuild \
    && pip install --upgrade recommonmark
