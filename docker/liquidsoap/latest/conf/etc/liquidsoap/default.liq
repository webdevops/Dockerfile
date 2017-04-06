#!/usr/bin/liquidsoap

# Lines starting with # are comments, they are ignored.

# Log path
set("log.file.path","/dev/null")

# Print log messages to the console,
# can also be done by passing the -v option to liquidsoap.
set("log.stdout", true)

# Use the telnet server for requests
{{if .Env.LIQUIBASE_TELNET}}
set("server.telnet", true)
{{end}}

##################
# Settings
##################

DJ_stream = "{{.Env.LIQUIBASE_STREAM_INPUT}}"

PLAYLIST_default = {{.Env.LIQUIBASE_PLAYLIST_DEFAULT}}
PLAYLIST_day     = {{.Env.LIQUIBASE_PLAYLIST_DAY}}
PLAYLIST_night   = {{.Env.LIQUIBASE_PLAYLIST_NIGHT}}

META_name  = "{{.Env.LIQUIBASE_META_NAME}}"
META_genre = "{{.Env.LIQUIBASE_META_GENRE}}"
META_url   = "{{.Env.LIQUIBASE_META_URL}}"
META_desc  = "{{.Env.LIQUIBASE_META_DESCRIPTION}}"

## fetch dj stream
DJ_input  = input.http(DJ_stream)

## fadeout dj
#DJ_input  = fade.out(merge_tracks(DJ_input))

# Play user requests if there are any,
# otherwise one of our playlists,
# and the default file if anything goes wrong.
PLAYLIST_input = fallback([
	switch([({ {{.Env.LIQUIBASE_PLAYLIST_DAY_TIMERANGE}} }, PLAYLIST_day),
                ({ {{.Env.LIQUIBASE_PLAYLIST_NIGHT_TIMERANGE}} }, PLAYLIST_night)]),
	PLAYLIST_default
])

## add fade
PLAYLIST_input = smart_crossfade(fade_out=0.5, fade_in=0.5, PLAYLIST_input)

## set title
PLAYLIST_input = rewrite_metadata(
	[
	    ("title", "$(title)"),
        ("comment", "{{.Env.LIQUIBASE_META_URL}}")
    ],
    PLAYLIST_input
)

# Add the ability to relay live shows
ALL_input = fallback(track_sensitive=false, [DJ_input, PLAYLIST_input])

#################
# Output
#################

{{.Env.LIQUIBASE_OUTPUT}}
{{.Env.LIQUIBASE_OUTPUT_1}}
{{.Env.LIQUIBASE_OUTPUT_2}}
{{.Env.LIQUIBASE_OUTPUT_3}}
{{.Env.LIQUIBASE_OUTPUT_4}}
{{.Env.LIQUIBASE_OUTPUT_5}}
{{.Env.LIQUIBASE_OUTPUT_6}}
{{.Env.LIQUIBASE_OUTPUT_7}}
{{.Env.LIQUIBASE_OUTPUT_8}}
{{.Env.LIQUIBASE_OUTPUT_9}}
{{.Env.LIQUIBASE_OUTPUT_10}}
{{.Env.LIQUIBASE_OUTPUT_11}}
{{.Env.LIQUIBASE_OUTPUT_12}}
{{.Env.LIQUIBASE_OUTPUT_13}}
{{.Env.LIQUIBASE_OUTPUT_14}}
{{.Env.LIQUIBASE_OUTPUT_15}}
{{.Env.LIQUIBASE_OUTPUT_16}}
{{.Env.LIQUIBASE_OUTPUT_17}}
{{.Env.LIQUIBASE_OUTPUT_18}}
{{.Env.LIQUIBASE_OUTPUT_19}}
{{.Env.LIQUIBASE_OUTPUT_20}}
