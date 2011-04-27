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

$SCHEMA = array("user" => array());

/**
 * Fetches data from the directory
 * @param null $schema If specified, restricts the list to this schema
 * @return void
 */
function directory_fetch($schema = null) {

    global $SCHEMA;

    $join = array();
    if($schema) {
        $join = $SCHEMA[$schema]['join'];
    }

    return ow_select(OW_DIRECTORY, array(), array('join' => $join));
}


function directory_update($oid, $attrs, $schema = null) {

    $entry = directory_get($oid);

    if(!$entry) {
        throw new Exception('Trying to update unknown entry. Were you trying to create one ?');
    }
    
    
    return directory_write($oid, $attrs, $schema);
    
}


function directory_add($attrs, $schema = null) {

    if(!empty($attrs['oid'])) {
            throw new Exception("You can't specify an OID when adding");
        }

    return directory_write(null, $attrs, $schema);
}

// TODO suportar SCHEMA e não JOIN (Join deve ser LOW LEVEL)
// QUANDO passado o SCHEMA, os joins, etc acontecem automaticamente
// E pode ser acessado como directory.php?schema=xxxxx
/**
 * @param  $object
 * { uid: unique_id,
 *   cn: "Common name",
 *   contacts: [ { contact1 }, { contact2 }, ...]
 * }
 *
 * @return void
 */
function directory_write($oid, $attrs, $schema = null) { // TODO como vai ficar o JOIN aqui?

    global $SCHEMA;

    // TODO validation baseada no schema

    $params = array();
    if($schema) {
        $params['join'] = $SCHEMA[$schema]['join'];

    }
    $attrs['`schema`'] = $schema;

    //print_r($attrs);
    //echo "directory write";
    return ow_write(OW_DIRECTORY, $oid, $attrs, $params);

}


function directory_get($oid, $schema = null) {

    global $SCHEMA;

    $cond = array(OW_DIRECTORY.".oid = '$oid'");

    $params = array();

    if($schema) {
        $params['join'] = $SCHEMA[$schema]['join'];
    }

    $data = ow_select(OW_DIRECTORY, $cond, $params);

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

