<?php

@session_start();
@ob_start();
date_default_timezone_set('Asia/Kolkata');
$GLOBALS['AppConfig'] = array();
$GLOBALS['AppConfig']['PhysicalPath'] = dirname(__FILE__);

if (!file_exists($GLOBALS['AppConfig']['PhysicalPath'].'/configure.local.php')) {
    throw new \Exception('Please create configure.local.php to define database connection');
} else {
    require_once $GLOBALS['AppConfig']['PhysicalPath'].'/configure.local.php';
}

use TAS\Core\DB;

$GLOBALS['db'] = new DB(HOST, LOCAL_USER, LOCAL_PASSWORD, LOCAL_DB);
