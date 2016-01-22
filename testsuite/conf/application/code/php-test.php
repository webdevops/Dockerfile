<?php

switch($_GET['test']) {
    case 'sha1':
        echo sha1('webdevops');
        break;

    case 'php_ini_scanned_files':
        echo php_ini_scanned_files();
        break;
}

