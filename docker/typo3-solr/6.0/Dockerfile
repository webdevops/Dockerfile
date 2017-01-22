#++++++++++++++++++++++++++++++++++++++
# Solr Docker container
#++++++++++++++++++++++++++++++++++++++
#
# Image:
#   solr (official)
#       https://hub.docker.com/_/solr/
#       https://hub.docker.com/r/library/solr/tags/
#       https://github.com/docker-solr/docker-solr
#       Infos
#           default Solr port: 8983
#
# TYPO3
#   typo3 solr extension
#       https://github.com/docker-solr/docker-solr
#       https://docs.typo3.org/typo3cms/extensions/solr/Appendix/VersionMatrix.html
#       Docker relevant links
#           https://github.com/TYPO3-Solr/ext-solr/blob/release-6.0.x/Dockerfile
#           https://github.com/TYPO3-Solr/ext-solr/tree/release-6.0.x/Resources/Private/Solr
#
#
#++++++++++++++++++++++++++++++++++++++
FROM solr:6.3.0
ENV TERM linux

RUN rm -fR /opt/solr/server/solr/*

COPY ./conf/ /opt/solr/server/solr

USER root

RUN chown -R solr:solr /opt/solr/server/solr/

USER solr

VOLUME ["/opt/solr/server/solr/data"]
