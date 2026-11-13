<?php

/*
 * Sends one request to PHP-FPM over FastCGI, without a web server, and prints the response of PHP-FPM: the headers,
 * an empty line and the body. The image tests use it to request PHP-FPM in the images without a web server, and to
 * set the parameters that a web server would set, e.g. HTTP_X_FORWARDED_FOR.
 *
 *   php -n fastcgi-request.php <host:port> <script> [<query string>] [NAME=VALUE ...]
 *
 * <script> is the path of the script, e.g. /app/php-test.php, or a path that PHP-FPM answers itself, e.g. its ping
 * path. NAME=VALUE sets a FastCGI parameter, e.g. HTTP_X_FORWARDED_FOR=127.0.0.3. A body on standard input is sent as
 * the request body of a POST request, if REQUEST_METHOD=POST is given.
 *
 * Exits with 0 when PHP-FPM ended the request, 2 when it closed the connection before, 1 when it cannot be reached.
 * What PHP-FPM sends on its stderr stream goes to stderr.
 */

const FCGI_BEGIN_REQUEST = 1;
const FCGI_END_REQUEST = 3;
const FCGI_PARAMS = 4;
const FCGI_STDIN = 5;
const FCGI_STDOUT = 6;
const FCGI_STDERR = 7;

function record(int $type, string $content): string
{
    return pack('CCnnCx', 1, $type, 1, strlen($content), 0) . $content;
}

function length(string $value): string
{
    return strlen($value) < 128 ? chr(strlen($value)) : pack('N', strlen($value) | 0x80000000);
}

[, $address, $script] = $argv + [null, null, null];
if ($address === null || $script === null) {
    fwrite(STDERR, "Usage: php -n fastcgi-request.php <host:port> <script> [<query string>] [NAME=VALUE ...]\n");
    exit(1);
}

$query = $argv[3] ?? '';
$parameters = [
    'GATEWAY_INTERFACE' => 'CGI/1.1',
    'SERVER_SOFTWARE' => 'image-tests',
    'SERVER_PROTOCOL' => 'HTTP/1.1',
    'SERVER_NAME' => 'localhost',
    'SERVER_ADDR' => '127.0.0.1',
    'SERVER_PORT' => '80',
    'REMOTE_ADDR' => '127.0.0.1',
    'REMOTE_PORT' => '54321',
    'REQUEST_METHOD' => 'GET',
    'DOCUMENT_ROOT' => dirname($script),
    'SCRIPT_FILENAME' => $script,
    'SCRIPT_NAME' => '/' . basename($script),
    'REQUEST_URI' => '/' . basename($script) . ($query === '' ? '' : '?' . $query),
    'QUERY_STRING' => $query,
];
foreach (array_slice($argv, 4) as $argument) {
    [$name, $value] = explode('=', $argument, 2) + [1 => ''];
    $parameters[$name] = $value;
}

// PHP-FPM answers its ping and status paths by SCRIPT_NAME
if (!str_ends_with($script, '.php')) {
    $parameters['SCRIPT_NAME'] = $script;
    $parameters['REQUEST_URI'] = $script . ($query === '' ? '' : '?' . $query);
}

$body = $parameters['REQUEST_METHOD'] === 'POST' ? stream_get_contents(STDIN) : '';
if ($body !== '') {
    $parameters['CONTENT_LENGTH'] = (string) strlen($body);
    $parameters['CONTENT_TYPE'] ??= 'application/x-www-form-urlencoded';
}

$socket = @stream_socket_client('tcp://' . $address, $errorCode, $errorMessage, 5);
if ($socket === false) {
    fwrite(STDERR, sprintf("Cannot connect to %s: %s\n", $address, $errorMessage));
    exit(1);
}

stream_set_timeout($socket, 120);

$request = record(FCGI_BEGIN_REQUEST, pack('nCx5', 1, 0));
$pairs = '';
foreach ($parameters as $name => $value) {
    $pairs .= length($name) . length($value) . $name . $value;
}

foreach (str_split($pairs, 65535) as $chunk) {
    $request .= record(FCGI_PARAMS, $chunk);
}

$request .= record(FCGI_PARAMS, '');
foreach ($body === '' ? [] : str_split($body, 65535) as $chunk) {
    $request .= record(FCGI_STDIN, $chunk);
}

$request .= record(FCGI_STDIN, '');
fwrite($socket, $request);

$read = static function (int $length) use ($socket): ?string {
    $data = '';
    while (strlen($data) < $length) {
        $chunk = fread($socket, $length - strlen($data));
        if ($chunk === false || $chunk === '') {
            return null;
        }

        $data .= $chunk;
    }

    return $data;
};

while (($header = $read(8)) !== null) {
    $record = unpack('Cversion/Ctype/nid/nlength/Cpadding', $header);
    $content = $record['length'] > 0 ? $read($record['length']) : '';
    if ($content === null || ($record['padding'] > 0 && $read($record['padding']) === null)) {
        break;
    }

    match ($record['type']) {
        FCGI_STDOUT => fwrite(STDOUT, $content),
        FCGI_STDERR => fwrite(STDERR, $content),
        FCGI_END_REQUEST => exit(0),
        default => null,
    };
}

fwrite(STDERR, "PHP-FPM closed the connection before it ended the request\n");
exit(2);
