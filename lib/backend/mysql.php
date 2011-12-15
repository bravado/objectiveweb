<?php
/**
 *
 * ObjectiveWeb
 * The default "mysql" backend
 *
 * Provides:
 *  TableStore - A handler that maps to one or more SQL tables
 *  ObjectStore - Schemaless storage backed by a single SQL table
 *
 * Configuration parameters
 *  MYSQL_HOST
 *  MYSQL_USER
 *  MYSQL_PASS
 *  MYSQL_DB
 *
 * User: guigouz
 * Date: 12/05/11
 * Time: 20:39
 */

defined('MYSQL_HOST') or define('MYSQL_HOST', 'localhost');
defined('MYSQL_USER') or define('MYSQL_USER', 'objectiveweb');
defined('MYSQL_PASS') or define('MYSQL_PASS', null);
defined('MYSQL_DB') or define('MYSQL_DB', 'objectiveweb');

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if ($db === FALSE) {
    throw new Exception($db->error);
}

$db->set_charset(OW_CHARSET);

class TableStore extends OWHandler
{
    // This handler params
    var $table = null;

    // Schema for all the related tables
    var $db_schema = array();

    // TODO associations
    var $hasMany = array();
    var $belongsTo = array();

    function has_field($field) {
        foreach($this->db_schema as $table) {
            if(isset($table['fields'][$field])) {
                return true;
            }
        }

        return false;
    }


    function init()
    {
        global $db;

        if (empty($this->table)) {
            throw new Exception(_('Invalid table specified!'), 500);
        }

        $query = sprintf('describe `%s`', $db->escape_string($this->table));
        $result = $db->query($query);

        if($result === FALSE) {
            throw new Exception($db->error, 500);
        }
        else {

            $this->db_schema[$this->table] = array('pk' => NULL, 'fields' => array());
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    if($data['Key'] == 'PRI') {
                        if($this->db_schema[$this->table]['pk'] != NULL) {
                            if(is_array($this->db_schema[$this->table]['pk'])) {
                                $this->db_schema[$this->table['pk']][] = $data['Field'];
                            }
                            else {
                                $this->db_schema[$this->table]['pk'] = array($this->db_schema[$this->table]['pk'], $data['Field']);
                            }
                        }
                        else {
                            $this->db_schema[$this->table]['pk'] = $data['Field'];
                        }
                    }
                    $this->db_schema[$this->table]['fields'][$data['Field']] = $data;
                }
            }
        }



        //print_r($this); exit;

        // TODO check if all necessary tables exist (meta, versioning, etc)
    }

    function get($oid)
    {
        $object = $this->fetch(array(sprintf("%s.%s = '%s'", $this->table, $this->db_schema[$this->table]['pk'], $oid)));

        if ($object) {
            return $object[0];
        }
        else {
            return null;
        }
    }

    function fetch($cond = array())
    {
        return ow_select($this->table, $cond, array('pk' => $this->db_schema[$this->table['pk']]));
    }

    function create($data = null, $owner = null, $metadata = array())
    {
        // TODO autogenerate ID if its char(36)
        if ($this->db_schema[$this->table]['pk'] == 'oid' && empty($data['oid'])) {
            $data['oid'] = ow_oid();
        }

        // Won't cache metadata on the main table
        $oid = ow_insert($this->table, $data);

        if (empty($oid)) {
            $oid = @$data[$this->db_schema[$this->table]['pk']];
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
        ow_update($this->table, array($this->db_schema[$this->table]['pk'] => $oid), $data);

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
        // TODO Implementar DELETE excluindo o OID de todas as tabelas auxiliares
        throw new Exception('DELETE não implementado');
    }


}


/**
 * ObjectStore
 *
 */
class ObjectStore extends TableStore
{

    function init()
    {
        parent::init();
    }

    function schema($name) {
        
    }

    function get($oid, $decoded = false)
    {
        $object = parent::get($oid);

        return $decoded ? json_decode($object['content'],true) : $object['content'];

    }

    function fetch($cond)
    {
        // TODO só preciso trazer o content de field!
        return parent::fetch($cond);
    }

    function create($params)
    {

        if(empty($params['oid'])) {
            $params['oid'] = ow_oid();
        }

        if(empty($params['schema'])) {
            $params['schema'] = 'ow/data';
        }

        foreach(array_keys($params) as $k) {
            if($this->has_field($k)) {
                $data[$k] = $params[$k];
            }
        }

        // TODO lang (if lang exists)
        // $data['lang'] = null;

        // TODO geolocation (if lat, long exists)

        // TODO hierarchy (if left, right exists)

        
        // Parameters are encoded as json
        // TODO index params (if indexed)
        $data['content'] = json_encode($params);

        return parent::create($data);
    }

    function write($oid, $data)
    {

        $new_data = array();

        $object = parent::get($oid);

        // We will overwrite the dynamic content
        unset($object['content']);

        // Changed and created fields should not be updated
        unset($object['changed']);
        unset($object['created']);
        
        // Now object has only valid fields, let's update those
        foreach(array_keys($object) as $k) {
            if(isset($data[$k])) {
                $object[$k] = $data[$k];
            }
        }

        // Final content will be everything on data + original fields
        // (dynamic content is ALWAYS OVERWRITTEN)
        $object['content'] = json_encode(array_merge($data, $object));

        return parent::write($oid, $object);
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
        throw new Exception($db->error, 500);
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


// Filesystem
function query($query)
{
    global $db;

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

}


/**
 *
 * Searches the ObjectiveWeb index
 *
 * @param  $domain The search domain or NULL for all domains
 * @param  $cond The search conditions Array
 * @param  $params ow_select() parameters
 * @return If the domain is specified, returns a list of Objects, otherwise returns a list of OIDs
 */
function ow_search($domain, $cond, $params = array())
{
    $defaults = array(
        'domain' => null,
        'fields' => '*',
        'join' => array(),
        'order' => array()
    );


    // TODO SELECT FROM ow_index ... inner join domain (se tiver)
}


/**
 * Fetches the next value from the named sequence
 * If the sequence does not exist, it is created with a default value of 1
 *
 *
 * @param  $sequence_id
 * @param  $start Starting counter for new sequences. Defaults to 1
 * @return void
 */
function ow_nexval($sequence_id, $start = 1)
{
    // TODO copiar código do cms original
}


/**
 * Indexes a field for the given oid
 *
 * @param  $oid
 * @param  $index_field
 * @param  $index_value
 * @return void
 */
function ow_index($oid, $index_field, $index_value)
{
    // TODO ow_insert(OW_INDEX, $oid, array('field' => $index_field, 'value' => $index_value)); ...
}
