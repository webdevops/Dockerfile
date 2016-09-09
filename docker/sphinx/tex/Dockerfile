#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/sphinx:tex
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:debian-9

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.55.3

RUN /usr/local/bin/apt-install \
    # General sphinx
    python-pil \
    python-yaml \
    python-sphinx \
    python-sphinx-rtd-theme \
    # GRAPHVIZ
    graphviz \
    ttf-dejavu \
    texlive-full \
    texlive-latex-extra \
    make \
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
    && pip install t3targets