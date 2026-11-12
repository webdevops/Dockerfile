<?php

/*
 * Asks Postfix on 127.0.0.1:25, from the local address <client address>, to relay a mail to a domain of its own, and
 * prints the answer to RCPT TO, in the image tests of POSTFIX_MYNETWORKS. Sends no mail.
 *
 *   php -n smtp-relay.php <client address>
 */

$context = stream_context_create(['socket' => ['bindto' => $argv[1] . ':0']]);
$smtp = stream_socket_client('tcp://127.0.0.1:25', $errorCode, $errorMessage, 5, STREAM_CLIENT_CONNECT, $context);
if ($smtp === false) {
    fwrite(STDERR, sprintf("Cannot connect to Postfix: %s\n", $errorMessage));
    exit(1);
}

stream_set_timeout($smtp, 10);
$answer = static function () use ($smtp): string {
    $answer = '';
    while (($line = fgets($smtp)) !== false) {
        $answer .= $line;
        // the last line of an answer has a space after the code
        if (($line[3] ?? ' ') === ' ') {
            break;
        }
    }

    return $answer;
};

$answer();
$last = '';
foreach (['EHLO image-test.example', 'MAIL FROM:<image-test@example.test>', 'RCPT TO:<someone@relay.example.test>'] as $command) {
    fwrite($smtp, $command . "\r\n");
    $last = $answer();
}

fwrite($smtp, "QUIT\r\n");
echo $last;
