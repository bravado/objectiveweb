<?php
/**
 *
 *
 * User: guigouz
 * Date: 03/01/12
 * Time: 15:29
 */

register_domain('directory', array('handler' => 'ObjectStore',
    'table' => OW_DIRECTORY,
    'get' => 'directory_get'
));

function directory_get($self, $id) {
    if(is_numeric($id) || is_array($id)) {
        return $self->get($id);
    }
    else {
        $entries = $self->fetch("oid=$id");
        $result = array();
        foreach($entries as $entry) {
            if(empty($entry['schema'])) {
                foreach($entry as $k => $v){
                    $result[$k] = $v;
                }
            }
            else {
                $result[$entry['schema']] = $entry;
            }
        }

        return $result;
    }
}