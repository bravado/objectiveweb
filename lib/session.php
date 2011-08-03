<?php
/**
 * ObjectiveWeb
 *
 * Session Library
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:11
 */

session_start();

global $_current_user;

$_current_user = array('oid' => '1234', 'acl' => array('1234'));

function current_user($field = null)
{
    global $_current_user;

    if ($field) {
        if (empty($_current_user[$field])) {
            throw new Exception('Invalid field');
        }
        else {
            return $_current_user[$field];
        }
    }
    else {
        return $_current_user;
    }

}
