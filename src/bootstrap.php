<?php


define('OW_ROOT', dirname(__DIR__));
define('ROOT', dirname(OW_ROOT));

// composer autoloader
require OW_ROOT.'/vendor/autoload.php';

// dependencies
require __DIR__.'/phpgacl/gacl.class.php';
require __DIR__.'/phpgacl/gacl_api.class.php';

// load core functions
include(__DIR__.'/core.php');

// Load the configuration file
defined('OW_CONFIG') || define('OW_CONFIG', ROOT.'/ow-config.php');

require_once OW_CONFIG;

defined('OW_DB_DRIVER') || define('OW_DB_DRIVER', getenv('OW_DB_DRIVER') ? getenv('OW_DB_DRIVER') : 'mysql');
defined('OW_DB') || define('OW_DB', getenv('OW_DB'));
defined('OW_DB_HOST') || define('OW_DB_HOST', getenv('OW_DB_HOST'));
defined('OW_DB_USER') || define('OW_DB_USER', getenv('OW_DB_USER'));
defined('OW_DB_PASS') || define('OW_DB_PASS', getenv('OW_DB_PASS'));
defined('OW_CHARSET') || define('OW_CHARSET', getenv('OW_CHARSET') ? getenv('OW_CHARSET') : 'utf8');

// Legacy mysqli library
require_once __DIR__.'/mysql.php';

// Register the directory
register_domain('directory', array(
    'handler' => '\Objectiveweb\Handler\Directory',
    'db_table_prefix' => 'ow_',
    'db_type' => OW_DB_DRIVER,
    'db_host' => OW_DB_HOST,
    'db_user' => OW_DB_USER,
    'db_password' => OW_DB_PASS,
    'db_name' => OW_DB
));

// Initialize applications
foreach($_apps as $_app) {
    require_once $_app;
}
