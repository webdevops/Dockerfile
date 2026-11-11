#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/dockerfile-build-env:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-22.04
ARG TARGETARCH

RUN apt-install \
        gpg-agent \
        apt-transport-https \
        ca-certificates \
        software-properties-common \
        git \
    && curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - \
    && add-apt-repository "deb [arch=$TARGETARCH] https://download.docker.com/linux/ubuntu    $(lsb_release -cs)    stable" \
    && apt-install docker-ce \
    && usermod -aG docker application \
    && curl -LO https://storage.googleapis.com/container-structure-test/latest/container-structure-test-linux-$TARGETARCH \
    && chmod +x container-structure-test-linux-$TARGETARCH \
    && mv container-structure-test-linux-$TARGETARCH /usr/local/bin/container-structure-test \
    && docker-image-cleanup

WORKDIR /app

CMD ["gosu", "application", "bash"]
