<?php

/**
 * Image tests: a minimal SMTP server on 127.0.0.1:2525, a stand-in for a mail catcher such as Mailpit. Postfix
 * relays to it with POSTFIX_RELAYHOST=[127.0.0.1]:2525; it stores each message in /tmp/image-tests/mail/, see PostfixChecks.
 */

declare(strict_types=1);

$server = stream_socket_server('tcp://127.0.0.1:2525', $errorCode, $errorMessage);
if ($server === false) {
    fwrite(STDERR, $errorMessage . PHP_EOL);
    exit(1);
}

@mkdir('/tmp/image-tests/mail', 0777, true);
while ($client = stream_socket_accept($server, -1)) {
    fwrite($client, "220 image-test-sink ESMTP\r\n");
    $sender = '';
    $message = null;
    while (($line = fgets($client)) !== false) {
        if ($message !== null) {
            if (rtrim($line, "\r\n") === '.') {
                file_put_contents('/tmp/image-tests/mail/' . uniqid('', true) . '.eml', $sender . $message);
                $message = null;
                fwrite($client, "250 stored\r\n");
            } else {
                $message .= $line;
            }

            continue;
        }

        $command = strtoupper(substr($line, 0, 4));
        if ($command === 'MAIL') {
            // the envelope sender, e.g. the one of sendmail -f, as the first header of the stored message
            $sender = 'X-Image-Test-Mail-From: ' . trim(substr($line, 10)) . "\r\n";
            fwrite($client, "250 image-test-sink\r\n");
        } elseif ($command === 'DATA') {
            $message = '';
            fwrite($client, "354 end with .\r\n");
        } elseif ($command === 'QUIT') {
            fwrite($client, "221 bye\r\n");
            break;
        } else {
            fwrite($client, "250 image-test-sink\r\n");
        }
    }

    fclose($client);
}
