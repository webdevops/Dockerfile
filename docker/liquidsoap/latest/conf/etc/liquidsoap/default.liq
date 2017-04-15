#!/usr/bin/liquidsoap

# Lines starting with # are comments, they are ignored.

# Log path
set("log.file.path","/dev/null")

# Print log messages to the console,
# can also be done by passing the -v option to liquidsoap.
set("log.stdout", true)

# Use the telnet server for requests
{{if .Env.LIQUIDSOAP_TELNET}}
set("server.telnet", true)
{{else}}
set("server.telnet", false)
{{end}}

##################
# Settings
##################

DJ_stream = "{{.Env.LIQUIDSOAP_STREAM_INPUT}}"

PLAYLIST_default = {{.Env.LIQUIDSOAP_PLAYLIST_DEFAULT}}
PLAYLIST_day     = {{.Env.LIQUIDSOAP_PLAYLIST_DAY}}
PLAYLIST_night   = {{.Env.LIQUIDSOAP_PLAYLIST_NIGHT}}

META_name  = "{{.Env.LIQUIDSOAP_META_NAME}}"
META_genre = "{{.Env.LIQUIDSOAP_META_GENRE}}"
META_url   = "{{.Env.LIQUIDSOAP_META_URL}}"
META_desc  = "{{.Env.LIQUIDSOAP_META_DESCRIPTION}}"

## fetch dj stream
DJ_input  = input.http(DJ_stream)

## fadeout dj
#DJ_input  = fade.out(merge_tracks(DJ_input))

# Play user requests if there are any,
# otherwise one of our playlists,
# and the default file if anything goes wrong.
PLAYLIST_input = fallback([
	switch([({ {{.Env.LIQUIDSOAP_PLAYLIST_DAY_TIMERANGE}} }, PLAYLIST_day),
                ({ {{.Env.LIQUIDSOAP_PLAYLIST_NIGHT_TIMERANGE}} }, PLAYLIST_night)]),
	PLAYLIST_default
])

## add fade
PLAYLIST_input = smart_crossfade(fade_out=0.5, fade_in=0.5, PLAYLIST_input)

## set title
PLAYLIST_input = rewrite_metadata(
	[
	    ("title", "$(title)"),
        ("comment", "{{.Env.LIQUIDSOAP_META_URL}}")
    ],
    PLAYLIST_input
)

# Add the ability to relay live shows
ALL_input = fallback(track_sensitive=false, [DJ_input, PLAYLIST_input])

#################
# Output
#################

{{.Env.LIQUIDSOAP_OUTPUT}}
{{.Env.LIQUIDSOAP_OUTPUT_1}}
{{.Env.LIQUIDSOAP_OUTPUT_2}}
{{.Env.LIQUIDSOAP_OUTPUT_3}}
{{.Env.LIQUIDSOAP_OUTPUT_4}}
{{.Env.LIQUIDSOAP_OUTPUT_5}}
{{.Env.LIQUIDSOAP_OUTPUT_6}}
{{.Env.LIQUIDSOAP_OUTPUT_7}}
{{.Env.LIQUIDSOAP_OUTPUT_8}}
{{.Env.LIQUIDSOAP_OUTPUT_9}}
{{.Env.LIQUIDSOAP_OUTPUT_10}}
{{.Env.LIQUIDSOAP_OUTPUT_11}}
{{.Env.LIQUIDSOAP_OUTPUT_12}}
{{.Env.LIQUIDSOAP_OUTPUT_13}}
{{.Env.LIQUIDSOAP_OUTPUT_14}}
{{.Env.LIQUIDSOAP_OUTPUT_15}}
{{.Env.LIQUIDSOAP_OUTPUT_16}}
{{.Env.LIQUIDSOAP_OUTPUT_17}}
{{.Env.LIQUIDSOAP_OUTPUT_18}}
{{.Env.LIQUIDSOAP_OUTPUT_19}}
{{.Env.LIQUIDSOAP_OUTPUT_20}}
