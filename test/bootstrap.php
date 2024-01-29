<?php
/**
 * php ./vendor/phpunit/phpunit/phpunit --bootstrap ./test/bootstrap.php ./test/.
 */
$baseDir = dirname(dirname(__FILE__));

if (file_exists($baseDir.'/vendor/autoload.php')) {
    require_once $baseDir.'/vendor/autoload.php';
} else {
    require_once '../vendor/autoload.php';
}

require dirname(__FILE__).'/configure.php';
$GLOBALS['db']->Debug = false;
$GLOBALS['AppConfig']['DeveloperMode'] = true;
