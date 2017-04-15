#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/liquidsoap:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest

# Install services
RUN /usr/local/bin/apt-install \
        liquidsoap \
        liquidsoap-plugin-all \
        mplayer \
        gstreamer0.10-plugins-base \
        gstreamer0.10-plugins-good \
    && docker-image-cleanup

ENV LIQUIDSOAP_USER            "liquidsoap"
ENV LIQUIDSOAP_TELNET           "1"

ENV LIQUIDSOAP_SCRIPT          "/opt/docker/etc/liquidsoap/default.liq"
ENV LIQUIDSOAP_TEMPLATE        "/opt/docker/etc/liquidsoap/default.liq"

ENV LIQUIDSOAP_STREAM_INPUT     "http://icecast:8000/live"

ENV LIQUIDSOAP_PLAYLIST_DEFAULT "audio_to_stereo(single('/opt/docker/etc/liquidsoap/default.mp3'))"

ENV LIQUIDSOAP_PLAYLIST_DAY     "playlist('/opt/docker/etc/liquidsoap/playlist-day.pls')"
ENV LIQUIDSOAP_PLAYLIST_DAY_TIMERANGE "4h-2h"

ENV LIQUIDSOAP_PLAYLIST_NIGHT   "playlist('/opt/docker/etc/liquidsoap/playlist-night.pls')"
ENV LIQUIDSOAP_PLAYLIST_NIGHT_TIMERANGE "2h-14h"

ENV LIQUIDSOAP_OUTPUT "output.icecast(%mp3(bitrate=128),host='localhost',port=8000,password='secretpassword',mount='liquidsoap-128',name=META_name,genre=META_genre,url=META_url,description=META_desc,ALL_input)"

ENV LIQUIDSOAP_META_NAME        "Liquidsoap Docker"
ENV LIQUIDSOAP_META_GENRE       ""
ENV LIQUIDSOAP_META_URL         ""
ENV LIQUIDSOAP_META_DESCRIPTION ""

COPY conf/ /opt/docker/

CMD ["liquidsoap"]

EXPOSE 1234
