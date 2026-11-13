<?php

/*
 * A stand-in for an IDE that Xdebug connects to, in the image tests: listens on <address:port> for 60 s and appends
 * the first packet of each connection, the <init> of the DBGp protocol, to <file>. Run it with php -n, so that Xdebug
 * does not debug the listener itself.
 *
 *   php -n dbgp-listener.php <address:port> <file>
 */

[, $address, $file] = $argv;
$server = stream_socket_server('tcp://' . $address, $errorCode, $errorMessage);
if ($server === false) {
    fwrite(STDERR, sprintf("Cannot listen on %s: %s\n", $address, $errorMessage));
    exit(1);
}

$end = time() + 60;
while (time() < $end) {
    $connection = @stream_socket_accept($server, 1);
    if ($connection === false) {
        continue;
    }

    stream_set_timeout($connection, 5);
    file_put_contents($file, fread($connection, 8192) . "\n", FILE_APPEND);
    fclose($connection);
}
