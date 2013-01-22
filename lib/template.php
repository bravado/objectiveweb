<?php
/**
 * ObjectiveWeb
 *
 * Template Engine
 *
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:52
 */

// To disable Smarty, set SMARTY_DIR to false
defined('SMARTY_DIR') || define('SMARTY_DIR', dirname(__FILE__).'/smarty/');

if(SMARTY_DIR) {
    require_once(SMARTY_DIR . 'Smarty.class.php');
}
