#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/sphinx:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

RUN /usr/local/bin/apk-install \
    # General sphinx
    py-pillow \
    py-yaml \
    py-sphinx \
    py-sphinx_rtd_theme \
    # GRAPHVIZ
    graphviz \
    ttf-dejavu \
    && pip install --upgrade sphinxcontrib-googlechart \
    && pip install --upgrade sphinxcontrib-googlemaps \
    && pip install --upgrade sphinxcontrib-httpdomain \
    && pip install --upgrade sphinxcontrib-slide \
    && pip install --upgrade sphinx-autobuild \
    && pip install --upgrade recommonmark \
    # TYPO3 stuff
    && pip install t3SphinxThemeRtd \
    && pip install t3fieldlisttable \
    && pip install t3tablerows \
    && pip install t3targets \
    && /usr/local/bin/docker-image-cleanup
