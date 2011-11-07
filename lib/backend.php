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
     * @param $oid
     * @param $name
     * @return Array attachment info
     */
    function attachment($oid, $name) {
        $filename = sprintf("%s/%s/%s/%s", OW_CONTENT, $this->id, $oid, $name);

        if(!is_readable($filename)) {
            throw new Exception('Attachment not found', 404);
        }
        else {
            return array('url' => sprintf("%s/%s/%s/%s", OW_CONTENT_URL, $this->id, $oid, $name));
        }
    }

    /**
     * Creates/updates an attachment
     * @param $oid
     * @param Array $data describing the attachment
     *  array("name" => "file_name.ext", "type" => "mime/type", "data" => "file_data")
     * @return Array
     */
    function attach($oid, $data) {
        $directory = sprintf("%s/%s/%s", OW_CONTENT, $this->id, $oid);

        if(!is_dir($directory)) {
            if(file_exists($directory)) {
                throw new Exception("Cannot write to $directory", 500);
            }

            mkdirs($directory);
        }

        $filename = sprintf("%s/%s", $directory, $data['name']);


        file_put_contents($filename, $data['data']);

        return array("ok" => 1);
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
    var $extends = array();

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



/**
 * Low-level SELECT Helper
 *
 * @param  $domain
 * @param  $cond
 * @param array $params
 * @return Array
 */
function ow_select($table, $cond, $params = array())
{

    global $db;

    // TODO rever params, não deveria passar o tables aqui ?
    // esse cara realmente sabe demais...
    $defaults = array(
        'pk' => 'id',
        'fields' => '*',
        'join' => array()
    );

    $params = array_merge($defaults, $params);


    $query = "select {$params['fields']} from {$table}";

    /*
    if ($params['extends']) {

        foreach (array_keys($params['extends']) as $joined) {
            $query .= " inner join $joined on {$table}.{$params['pk']} = $joined.oid";
        }
    }
    if (!empty($cond['acl'])) {
        // TODO implementar ACL
        throw new Exception('ACL não implementado');
    }


    */
    if (!empty($cond)) {
        // TODO usar prepared statements / escape()

        if (!is_array($cond)) {
            $cond = explode('&', $cond);
        }

        // TODO optimize?
        $query .= " where " . implode(" and ", $cond);

    }

    if (!empty($params['order'])) {
        // TODO if is array...
        $query .= " order by " . $params['order'];
    }

    // paging
    //if ($params['iDisplayStart'] && $params['iDisplayLength'] != -1) {
    //    $query .= " limit " . $db->escape_string($params['iDisplayStart']) . ", " .
    //              $db->escape_string($params['iDisplayLength']);
    //}

    if (defined('DEBUG')) {
        error_log($query);
    }

    $result = $db->query($query);


    if ($result === FALSE) {
        throw new Exception($db->error);
    }

    $results = array();
    if ($result->num_rows > 0) {
        while ($data = $result->fetch_assoc()) {
            $results[] = $data;
        }
    }


    return $results;

    // TODO query
    // TODO se todos podem ler - vai vir o usuário logado até aqui quando ?
    // porque se passar acl => user, um oid pra todomundo não vai ter essa acl (user)
    // posso querer pesquisar oid = user, group OR object é PUBLIC

    // Solução (5:31 AM) - SEMPRE vai existir uma permission (everybody/anonymous) ?

    // em ow_acl owner = 0 => todomundo ?

    // dono de um conteúdo não precisa estar no ACL pq ele sempre pode tudo
    // campo específico do módulo content, não tem porque trazer até aqui


}


/**
 * Low level SQL INSERT helper
 *
 * @throws Exception
 * @param  $domain
 * @param  $oid
 * @param  $data
 * @return string
 */
function ow_insert($table, $data)
{

    global $db;

    if (empty($data)) {
        respond(array("error" => 'Trying to write nothing'), 405);
    }

    // TODO verificar schema

    $bind_params = array('');
    $query_fields = array();

    foreach ($data as $k => $v) {

        if (is_array($v)) { // TODO or is_class/is_object ?
            $data[$k] = json_encode($v);
        }

        $query_fields[] = "`$k`";
        $bind_params[0] .= 's';
        $bind_params[] = &$data[$k];
    }

    $values = '?';
    for ($i = 1; $i < count($query_fields); $i++) {
        $values .= ',?';
    }

    $query = sprintf("insert into $table (%s) values (%s)", implode(",", $query_fields), $values);


    if (defined('DEBUG')) {
        error_log($query);
    }
    //print_r($bind_params);
    $stmt = $db->prepare($query);


    if ($stmt === FALSE) throw new Exception($db->error, 500);

    //print_r($bind_params);
    //print_r($stmt);
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);


    $stmt->close();

    return $db->insert_id;
}


/**
 *
 * Low level SQL UPDATE helper
 *
 * @throws Exception on database errors
 * @param  $table - The table to update
 * @param  $cond - The conditions to update
 * @param  $data - Associative Array of updated data
 * @return The object's oid
 */
function ow_update($table, $key, $data)
{

    global $db;

    if (empty($key)) throw new Exception('Key is required for UPDATEs', 405);

    if (empty($data)) return;

    $bind_params = array('');
    $query_args = array();


    foreach ($data as $k => $v) {

        if (is_array($v)) { // TODO or is_class/is_object ?
            $data[$k] = json_encode($v);
        }

        $query_args[] = "`$k` = ?";
        $bind_params[0] .= 's';
        $bind_params[] = &$data[$k];
    }

    $key_args = array();
    foreach ($key as $k => $v) {
        $key_args[] = "`$k` = ?";
        $bind_params[0] .= 's';
        $bind_params[] = &$key[$k];
    }

    $query = sprintf("update $table set %s where %s", implode(",", $query_args), implode("AND", $key_args));

    if (defined('DEBUG')) {
        error_log($query);
    }

    $stmt = $db->prepare($query);


    if ($stmt === FALSE) throw new Exception($db->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

    return $key;
}


/**
 * Low level SQL DELETE helper
 * @throws Exception
 * @param  $table
 * @param  $oid
 * @return void
 */
function ow_delete($table, $oid)
{
    global $db;

    if (!is_array($oid)) {
        $oid = array('oid' => $oid);
    }

    $query_args = array();
    $bind_params = array('');

    foreach ($oid as $k => $v) {

        $query_args[] = "`$k` = ?";
        $bind_params[0] .= 's';
        $bind_params[] = &$oid[$k];
    }

    $query = sprintf("delete from $table where %s", implode(' and ', $query_args));

    $stmt = $db->prepare($query);

    if (defined('DEBUG')) {
        echo $query;

    }
    //echo $query;
    if ($stmt === FALSE) throw new Exception($db->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

}

