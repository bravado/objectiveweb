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

require_once ROOT.'/config.php';

require_once OW_LIB.'/core.php';
require_once OW_LIB.'/directory.php';
require_once OW_LIB.'/content.php';
require_once OW_LIB.'/request.php';
require_once OW_LIB.'/session.php';