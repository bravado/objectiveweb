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

require_once ROOT.'/ow-config.php';
<<<<<<< HEAD
require_once OW_LIB.'/f3/base.php';
=======

defined('ATTACHMENT_ROOT') or define('ATTACHMENT_ROOT', ROOT . '/ow-content');


>>>>>>> 84c60c8656d21c3c72812cd22abf2d718533f0b9
require_once OW_LIB.'/classes.php';
require_once OW_LIB.'/util.php';

require_once OW_LIB.'/core.php';
require_once OW_LIB.'/request.php';
require_once OW_LIB.'/session.php';

<<<<<<< HEAD

//require_once ROOT.'/ow-apps.php'; // TODO schema_register() e não usar isso aqui ?
=======
require_once ROOT.'/ow-apps.php'; // TODO schema_register() e não usar isso aqui ?




>>>>>>> 84c60c8656d21c3c72812cd22abf2d718533f0b9
