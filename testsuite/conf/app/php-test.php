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

    default:
        header('HTTP/1.1 500 Internal Server Error');
        echo 'ERROR: Unknown test';
        break;
}

