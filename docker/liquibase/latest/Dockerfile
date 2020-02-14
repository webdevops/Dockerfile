#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/liquibase:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM openjdk:8

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV LIQUIBASE_VERSION="3.6.3" \
    LIQUIBASE_DRIVER="com.mysql.jdbc.Driver" \
    LIQUIBASE_CLASSPATH="/usr/share/java/mysql.jar" \
    LIQUIBASE_URL="" \
    LIQUIBASE_USERNAME="" \
    LIQUIBASE_PASSWORD="" \
    LIQUIBASE_CHANGELOG="liquibase.xml" \
    LIQUIBASE_CONTEXTS="" \
    LIQUIBASE_OPTS=""

COPY conf/ /opt/docker/

RUN set -x \
    && apt-get update \
	&& apt-get install -yq --no-install-recommends \
		libmariadb-java \
    && wget -q -O/tmp/liquibase.tar.gz "https://github.com/liquibase/liquibase/releases/download/liquibase-parent-${LIQUIBASE_VERSION}/liquibase-${LIQUIBASE_VERSION}-bin.tar.gz" \
    && mkdir -p /opt/liquibase \
    && tar -xzf /tmp/liquibase.tar.gz -C /opt/liquibase \
    && rm -f /tmp/liquibase.tar.gz \
    && chmod +x /opt/liquibase/liquibase \
    && ln -s /opt/liquibase/liquibase /usr/local/bin/ \
    && chmod +x /opt/docker/bin/entrypoint.sh \
	&& apt-get clean \
	&& rm -r /var/lib/apt/lists/* \
    && mkdir /liquibase \
    && ln -sf /opt/docker/bin/entrypoint.sh /entrypoint \
    # cleanup
    && apt-get autoremove -y -f \
    && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /liquibase
ENTRYPOINT ["/entrypoint"]

