vcl 4.0;

import std;
import directors;

backend default {
    .host = "<VARNISH_BACKEND_HOST>";
    .port = "<VARNISH_BACKEND_PORT>";
}
