#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/liquibase:mysql
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM java:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.2.0

ENV LIQUIBASE_VERSION   "3.5.3"
ENV LIQUIBASE_DRIVER    "com.mysql.jdbc.Driver"
ENV LIQUIBASE_CLASSPATH "/usr/share/java/mysql.jar"
ENV LIQUIBASE_URL       ""
ENV LIQUIBASE_USERNAME  ""
ENV LIQUIBASE_PASSWORD  ""
ENV LIQUIBASE_CHANGELOG "/liquibase/changelog.xml"
ENV LIQUIBASE_CONTEXTS  ""
ENV LIQUIBASE_OPTS      ""

COPY conf/ /opt/docker/

RUN apt-get update \
	&& apt-get install -yq --no-install-recommends \
		libmysql-java \
    && wget -q -O/tmp/liquibase.tar.gz "https://github.com/liquibase/liquibase/releases/download/liquibase-parent-${LIQUIBASE_VERSION}/liquibase-${LIQUIBASE_VERSION}-bin.tar.gz" \
    && mkdir -p /opt/liquibase \
    && tar -xzf /tmp/liquibase.tar.gz -C /opt/liquibase \
    && rm -f /tmp/liquibase.tar.gz \
    && chmod +x /opt/liquibase/liquibase \
    && ln -s /opt/liquibase/liquibase /usr/local/bin/ \
    && chmod +x /opt/docker/bin/entrypoint.sh \
	&& apt-get clean \
	&& rm -r /var/lib/apt/lists/*

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]

