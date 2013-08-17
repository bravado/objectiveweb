<?php
/**
 * Objectiveweb Directory
 * Provides the /directory domain, for storing arbitrary user/app data and handlers for authentication
 *
 * User: guigouz
 * Date: 03/01/12
 * Time: 15:29
 */

// Default directory table
defined('OW_DIRECTORY') or define('OW_DIRECTORY', 'ow_directory');

// Register the "directory" domain
register_domain('directory', array(
    'handler' => 'ObjectStore',
    'table' => OW_DIRECTORY,
    'get' => 'directory_get',
    'put' => 'directory_put',
    'post' => 'directory_post',
    'filter' => array(
        ''
    )
));

function directory_get($self, $id) {
    if (is_numeric($id) || is_array($id)) {
        return $self->get($id);
    }
    else {
        $entries = $self->fetch("oid=$id");
        if (count($entries)) {
            $result = array('oid' => $id);
            foreach ($entries as $entry) {
                if (empty($entry['namespace'])) {
                    foreach ($entry as $k => $v) {
                        $result[$k] = $v;
                    }
                }
                else {
                    $result[$entry['namespace']] = $entry;
                }
            }
        }
        else {
            $result = null;
        }

        return $result;
    }
}

function directory_password_filter($data) {
    if(empty($data['namespace']) && !empty($data['password'])) {
        $data['userPassword'] = md5($data['password']);
        unset($data['password']);
    }

    return $data;
}

function directory_post($handler, $data) {
    return $handler->post(directory_password_filter($data));
}

function directory_put($self, $id, $data) {
    if (!is_numeric($id)) {
        throw new Exception("Invalid ID for put (must be numeric)", 405);
    }

    return $self->put($id, directory_password_filter($data));
}

function ow_set_current_user($user) {
    if (is_array($user)) {
        $_SESSION['current_user'] = $user;
    }
    else {
        $user = get('directory', $user);
        if (!$user) {
            throw new Exception('Invalid user');
        }
        else {
            $_SESSION['current_user'] = $user;
        }
    }
}


class Acl extends OWFilter {

    var $id = "acl";

    function post($domain, $data) {
        if(isset($this->handler->table->fields['_owner']) && empty($data['_owner'])) {
            $data['_owner'] = current_user('oid');
        }

        return $data;
    }
}