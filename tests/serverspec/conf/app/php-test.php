<?php

switch($_GET['test']) {
    case 'sha1':
        echo sha1('webdevops');
        break;

    case 'version':
        echo 'PHP ' . phpversion();
        break;

    case 'php_sapi_name':
        echo php_sapi_name();
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
        $_SESSION['serverspec_upload'] = 'ok';
        session_write_close();
        session_start();
        $result = [
            'upload_progress_enabled' => (bool) ini_get('session.upload_progress.enabled'),
            'file_uploaded' => (bool) $fileUploaded,
            'session_persisted' => ($_SESSION['serverspec_upload'] ?? null) === 'ok',
        ];
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode($result);
        break;

    default:
        header('HTTP/1.1 500 Internal Server Error');
        echo 'ERROR: Unknown test';
        break;
}

