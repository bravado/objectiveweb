<?php
/**
 * ObjectiveWeb
 *
 * Library Initialization
 * 
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:05
 */

define('OW_ROOT', dirname(__FILE__));
define('OW_LIB', OW_ROOT.'/lib');
define('ROOT', dirname(OW_ROOT));
define('DOCUMENT_ROOT', substr(realpath($_SERVER['SCRIPT_FILENAME']), 0, -1 * strlen($_SERVER['SCRIPT_NAME'])));
// Set the correct objectiveweb url, even if called from another script
define('OW_URL', (isset($_SERVER['HTTPS']) ? "https" : "http"). "://{$_SERVER['SERVER_NAME']}"
                 .($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? ":{$_SERVER['SERVER_PORT']}" : "")
                 .substr(dirname(__FILE__).'/', strlen(DOCUMENT_ROOT)));


// Load the configuration file
defined('OW_CONFIG') || define('OW_CONFIG', ROOT.'/ow-config.php');

require_once OW_LIB.'/core.php';

require_once OW_CONFIG;

defined('DEBUG') or define('DEBUG', FALSE);

// Additional utility functions
require_once OW_LIB.'/util.php';

// Default backend config
defined('OW_BACKEND') or define('OW_BACKEND', dirname(__FILE__) . '/lib/backend/mysql.php');
defined('OW_CHARSET') or define('OW_CHARSET', 'utf8');

require_once OW_BACKEND;
require_once OW_LIB.'/router.php';
require_once OW_LIB.'/directory.php';
require_once OW_LIB.'/attachments.php';
require_once OW_LIB.'/template.php';


// Initialize applications
foreach($_apps as $_app) {
    require_once $_app;
}
