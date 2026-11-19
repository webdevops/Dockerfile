<?php

switch ($_GET['test']) {
    case 'sha1':
        echo sha1('webdevops');
        break;

    case 'version':
        echo 'PHP ' . phpversion();
        break;

    case 'server_name':
        echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? '', ENT_QUOTES);
        break;

    case 'php_sapi_name':
        echo PHP_SAPI;
        break;

    case 'get_loaded_extensions':
        $moduleList = array_merge(get_loaded_extensions(), get_loaded_extensions(true));
        echo implode("\n", $moduleList);
        break;

    case 'php_ini_scanned_files':
        echo php_ini_scanned_files();
        break;

    case 'process_user_id':
        echo 'UID:' . posix_getuid() . '#';
        break;

    case 'session_upload_progress':
        session_start();
        $upload = $_FILES['file'] ?? null;
        $fileUploaded = $upload && $upload['error'] === UPLOAD_ERR_OK
            && is_uploaded_file($upload['tmp_name'])
            && hash_file('sha256', $upload['tmp_name']) === hash_file('sha256', __DIR__ . '/pi-number.html');
        $_SESSION['image_test_upload'] = 'ok';
        session_write_close();
        session_start();
        $result = [
            'upload_progress_enabled' => (bool) ini_get('session.upload_progress.enabled'),
            'file_uploaded' => $fileUploaded,
            'session_persisted' => ($_SESSION['image_test_upload'] ?? null) === 'ok',
        ];
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode($result);
        break;

    case 'sleep':
        // time_limit=0 lifts max_execution_time first: in thread-safe PHP (8.6), the time in sleep() counts towards it
        if (isset($_GET['time_limit'])) {
            set_time_limit((int) $_GET['time_limit']);
        }

        sleep((int) ($_GET['seconds'] ?? 0));
        echo 'slept';
        break;

    case 'mail':
        echo mail('someone@example.test', 'Image test from ' . PHP_SAPI, 'Sent by the image tests') ? 'sent' : 'not sent';
        break;

    case 'allocate':
        // a string of the given number of MiB
        $memory = str_repeat('x', (int) ($_GET['mib'] ?? 0) * 1024 * 1024);
        echo 'allocated ' . strlen($memory);
        break;

    case 'busy':
        // runs, without waiting, for the given number of seconds
        $end = microtime(true) + (float) ($_GET['seconds'] ?? 0);
        while (microtime(true) < $end) {
            hash('sha256', 'webdevops');
        }

        echo 'finished';
        break;

    case 'error_log':
        // a fixed message: one from the request would let the request write into the log
        error_log('image-test-from-php-fpm');
        echo 'logged';
        break;

    case 'error_log_setting':
        echo ini_get('error_log');
        break;

    case 'warning':
        echo $undefinedImageTestVariable;
        echo 'done';
        break;

    case 'request':
        header('Content-Type: application/json');
        echo json_encode([
            'post' => count($_POST),
            'files' => array_map(static fn (array $file): int => $file['error'], $_FILES),
        ]);
        break;

    case 'opcache':
        $status = opcache_get_status(false);
        header('Content-Type: application/json');
        echo json_encode([
            'memory' => $status['memory_usage']['used_memory'] + $status['memory_usage']['free_memory'] + $status['memory_usage']['wasted_memory'],
            'interned_strings_buffer' => $status['interned_strings_usage']['buffer_size'],
            'max_cached_keys' => $status['opcache_statistics']['max_cached_keys'],
        ]);
        break;

    case 'process':
        header('Content-Type: application/json');
        echo json_encode([
            'pid' => getmypid(),
            'limits' => file_get_contents('/proc/self/limits'),
        ]);
        break;

    case 'date':
        echo date_default_timezone_get() . ' ' . date('T');
        break;

    case 'session_name':
        echo session_name();
        break;

    default:
        header('HTTP/1.1 500 Internal Server Error');
        echo 'ERROR: Unknown test';
        break;
}
