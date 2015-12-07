#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:14.04
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# System update
RUN /usr/local/bin/apt-upgrade
