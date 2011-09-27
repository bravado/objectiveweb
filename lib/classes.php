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

    /**
     * Creates/updates an attachment
     * @param $oid
     * @param Array $data describing the attachment
     *  array("name" => "file_name.ext", "type" => "mime/type", "data" => "file_data")
     * @return void
     */
    function attach($oid, $data) {
        echo "attaching ".print_r($data);
        $directory = sprintf("%s/%s/%s", ATTACHMENT_ROOT, $this->id, $oid);

        if(!is_dir($directory)) {
            if(file_exists($directory)) {
                throw new Exception("Cannot write to $directory", 500);
            }

            mkdirs($directory);
        }

        $filename = sprintf("%s/%s", $directory, $data['name']);
        
        file_put_contents($filename, $data['data']);
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

    var $db;

    function init() {

        if(empty($this->table)) {
            throw new Exception(_('Invalid table specified!'), 500);
        }

        $this->db = new Axon($this->table);
        // TODO $this->db->join(other_table)
        // $this->db->extend ?
        // hasOne ?
        // hasMany ?
        // belongsTo ?
    }

    function get($oid)
    {

        return $this->db->afindone(array(
                                 sprintf("%s = :oid", $this->pk),
                                 array(':oid' => $oid) ));
        
    }

    function fetch($criteria = null, $order = null, $limit = 0, $offset = 0)
    {

        return $this->db->find(empty($criteria) ? null : $criteria, $order, $limit, $offset, false);
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
    
    function schema() {
        $schema = F3::get('DB')->schema($this->table, 600);

        return $schema['result'];
    }
}