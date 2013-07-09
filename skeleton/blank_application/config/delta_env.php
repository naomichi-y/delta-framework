<?php
ini_set('display_errors', 0);

define('DELTA_ROOT_DIR', '{%DELTA_ROOT_DIR%}');
define('DELTA_LIBS_DIR', DELTA_ROOT_DIR . '/libs');
define('APP_ROOT_DIR', dirname(__DIR__));

require DELTA_LIBS_DIR . '/kernel/loader/Delta_BootLoader.php';

