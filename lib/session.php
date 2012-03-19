<?php
/**
 * ObjectiveWeb
 *
 * Session Library
 *
 * I bet there are lots of interesting stuff that can be added here (memcache, shared sessions and such)
 * For now, this file starts the session and define helpers for setting and querying the current user
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:11
 */

session_start();

/**
 * @param null $field
 * @return mixed
 * @throws Exception
 */
function current_user($field = null)
{
    $_current_user = $_SESSION['current_user'];

    if ($field) {
        if (!isset($_current_user[$field])) {
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

function set_current_user($user) {
    $_SESSION['current_user'] = $user;
}
