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
define('OW_URL', dirname(dirname($_SERVER['SCRIPT_NAME']))); // TODO fragile

require_once ROOT.'/ow-config.php';
require_once OW_LIB . '/f3/base.php';
require_once OW_LIB.'/backend.php';
require_once OW_LIB.'/util.php';

// Default db config
defined('DATABASE_PROVIDER') or define('DATABASE_PROVIDER', dirname(__FILE__) . '/lib/backend/mysql.php');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8');

require_once DATABASE_PROVIDER;

require_once OW_LIB.'/core.php';
require_once OW_LIB.'/request.php';
require_once OW_LIB.'/session.php';


require_once ROOT.'/ow-apps.php'; // TODO schema_register() e não usar isso aqui ?