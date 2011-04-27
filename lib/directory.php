<?php
/**
 * ObjectiveWeb
 *
 * Directory Library
 * 
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:05
 */

defined('OW_DIRECTORY') or define('OW_DIRECTORY', 'ow_directory');


$schema = array("user" => array());



function directory_list($schema = null) {

    $cond = array();
    if($schema) {
        $cond[] = "schema = '$schema'";
    }

    return ow_select('OW_DIRECTORY', $cond);
}


function directory_update($uid, $attrs) {
    $entry = directory_get($uid);

    if(!$entry) {
        throw new Exception('UPDATING unknown entry, were you trying to create it ?');
    }

    directory_write($uid, $attrs);
    
}


function directory_add($uid, $attrs) {

    $entry = directory_get($uid);


    if($entry) {
        throw new Exception('UID already exists');
    }
    else {
        directory_write($uid, $attrs);
    }
}

/**
 * @param  $object
 * { uid: unique_id,
 *   cn: "Common name",
 *   contacts: [ { contact1 }, { contact2 }, ...]
 * }
 *
 * @return void
 */
function directory_write($uid, $attrs, $join = array()) { // TODO como vai ficar o JOIN aqui?

    // TODO validation

    $params = array('key' => 'uid', 'join' => $join);

    //echo "directory write";
    return ow_write(OW_DIRECTORY, $uid, $attrs, $params);

}


function directory_get($uid, $join = array()) {

    $data = ow_select(OW_DIRECTORY, array("uid = '$uid'"));

    if(!empty($data)) {
        return $data[0];
    }
    else {
        return null;
    }
}

function directory_rename($old_uid, $new_uid) {

}

function directory_delete($object, $join = array()) {
    // TODO ow_delete
}

