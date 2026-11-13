#!/bin/sh
# A stand-in for sendmail in the image tests, see PHP_SENDMAIL_PATH: stores the mail that PHP pipes in, after a line
# with the arguments, in /tmp/image-tests/captured-mail/.
{
    echo "Arguments: $*"
    cat
} > "/tmp/image-tests/captured-mail/$(date +%s)-$$.eml"
