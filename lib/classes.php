<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 06/06/11
 * Time: 14:30
 */
 
class OWHandler {

    var $id;
    var $defaults = array();

    function _init($id, $params = array()) {
        $this->id = $id;

        foreach(array_merge($this->defaults, $params) as $param => $value) {
            // TODO verificar reserved keywords
            $this->$param = $value;
        }
        $this->init();
    }

    function init() {

    }
    
    /**
     * Manages metadata at $oid/$meta_key
     * @param  $meta_key
     * @param  $meta_value
     * @return void
     */
    function meta($oid, $meta_key, $meta_value = null)
    {

    }


    function acl($oid, $rules = null)
    {

    }

    /**
     * Retrieves an object from a domain given its OID
     *
     * @param  $domain
     * @param  $oid
     * @param array $params
     * @return void
     */
    function get($oid)
    {
    }


    function fetch($cond = array())
    {

    }


    /**
     * Creates a new Object on a domain
     *
     * @param null $data The object data
     * @param null $owner The object owner
     * @param null $metadata The object metadata

     * @return string|The object's new oid.
     */
    function create($data = null, $owner = null, $metadata = array())
    {

    }

    /**
     * Writes data to an existing object
     * @param  $oid
     * @param Array $data
     * @param Array $metadata
     * @return array The changed data
     */
    function write($oid, $data)
    {

    }

    function delete($oid)
    {

    }
}



class TableStore extends OWHandler
{
    // This handler params
    var $table = null;

    // TODO associations
    var $hasMany = array();
    var $belongsTo = array();

    var $pk = 'id';


    function init() {

        if(empty($this->table)) {
            throw new Exception(_('Invalid table specified!'), 500);
        }
    }

    function get($oid)
    {
        $object = $this->fetch(array(sprintf("%s.%s = '%s'", $this->table, $this->pk, $oid)));

        if ($object) {
            return $object[0];
        }
        else {
            return null;
        }
    }

    function fetch($cond = array())
    {
        return ow_select($this->table, $cond, array('pk' => $this->pk));
    }

    function create($data = null, $owner = null, $metadata = array())
    {
        // TODO autogenerate ID if its char(36)
        if($this->pk == 'oid' && empty($data['oid'])) {
            $data['oid'] = ow_oid();
        }

        // Won't cache metadata on the main table
        $oid = ow_insert($this->table, $data);

        if(empty($oid)) {
            $oid = @$data[$this->pk];
        }
        
        // Indexes and additional tables

        //if (!empty($metadata['acl'])) {
        //    $this->acl($oid, $metadata['acl']);
        //    unset($metadata['acl']);
        //}

        //if (!empty($metadata['links'])) {
            // TODO processar links
        //    unset($metadata['links']);
        //}

//        foreach ($metadata as $meta_key => $meta_value) {
//
//            if (is_array($meta_value)) {
//                $meta_value = json_encode($meta_value);
//            }
//
//            $this->meta($oid, $meta_key, $meta_value);
//        }

//        if ($this->params['tables']) {
//
//            foreach ($this->params['tables'] as $table => $fields) {
//
//                $schema_data = array();
//
//                foreach ($fields as $field) {
//
//                    if (!empty($data[$field])) {
//                        $schema_data[$field] = $data[$field];
//                    }
//                }
//
//                if (!empty($schema_data)) {
//                    $schema_data['oid'] = $oid;
//                    ow_insert($table, $schema_data);
//                }
//            }
//        }

        return $oid;
    }

    function write($oid, $data)
    {
        ow_update($this->table, array($this->pk => $oid), $data);

//        // TODO default acls devem vir do SCHEMA (db)
//
//
//        // if versioning is on
//        if ($this->params['versioning']) {
//            ow_insert(OW_VERSION, array('oid' => $oid, 'data' => $data));
//        }
//
//
//        if (!$this->params['tables']) {
//            return;
//        }
//
//        foreach ($this->params['tables'] as $table => $fields) {
//
//            $schema_data = array();
//
//            foreach ($fields as $field) {
//
//                if (!empty($data[$field])) {
//                    $schema_data[$field] = $data[$field];
//                }
//            }
//
//            if (!empty($schema_data)) {
//                ow_update($table, $oid, $schema_data);
//            }
//        }
//
//
//        if (!empty($metadata['acl'])) {
//            $this->acl($oid, $metadata['acl']);
//        }
//
//
//        if (!empty($params['index'])) {
//            foreach ($params['index'] as $index_field) {
//                if (!empty($data[$index_field])) {
//                    ow_index($oid, $index_field, $data[$index_field]);
//                }
//
//            }
//        }
//

        return $oid;
    }

    function delete($oid)
    {
        // TODO Implementar DELETE excluindo o OID de todas as tabelas (até OW_OBJECTS)
        throw new Exception('DELETE não implementado');
    }
}