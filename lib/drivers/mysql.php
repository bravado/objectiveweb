<?php
/**
 * The default "mysql" driver
 *
 * User: guigouz
 * Date: 12/05/11
 * Time: 20:39
 */

defined('MYSQL_HOST') or define('MYSQL_HOST', 'localhost');
defined('MYSQL_USER') or define('MYSQL_USER', 'objectiveweb');
defined('MYSQL_PASS') or define('MYSQL_PASS', null);
defined('MYSQL_DB') or define('MYSQL_DB', 'objectiveweb');

// Database Tables
defined('OW_OBJECTS') or define('OW_OBJECTS', 'ow_objects');
defined('OW_META') or define('OW_META', 'ow_meta');
defined('OW_INDEX') or define('OW_INDEX', 'ow_index');
defined('OW_VERSION') or define('OW_VERSION', 'ow_version');
defined('OW_LINKS') or define('OW_LINKS', 'ow_links');
defined('OW_ACL') or define('OW_ACL', 'ow_acl');
defined('OW_SEQUENCE') or define('OW_SEQUENCE', 'ow_sequence');


$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);


if ($db === FALSE) {
    throw new Exception($db->error);
}

$db->set_charset(DB_CHARSET);

class MysqlDriver extends OW_Driver
{
    var $domain;
    var $params;

    function MysqlDriver($domain, $params)
    {
        $defaults = array(
            'tables' => false,
            'versioning' => false
        );

        $this->params = array_merge($defaults, $params);
        $this->domain = $domain;
    }

    /**
     * Manages metadata at $oid/$meta_key
     * @param  $meta_key
     * @param  $meta_value
     * @return void
     */
    function meta($oid, $meta_key, $meta_value = null)
    {

        if (is_array($oid)) {
            $oid = $oid['oid'];
        }


        $data = array('oid' => $oid, 'meta_key' => $meta_key);

        if ($meta_value) {
            ow_delete(OW_META, $data);

            if (is_array($meta_value)) {

                //if(!empty($this->params['index'][$meta_key])) {
                // TODO tratar de indexar properties
                //}

                //foreach($meta_value as $val) {
                //    $data['meta_value'] = $val;
                //    ow_insert(OW_META, $data);
                //}

                $meta_value = json_encode($meta_value);
            }

            $data['meta_value'] = $meta_value;
            ow_insert(OW_META, $data);

        }
        else {
            return "META VALUE";
        }

    }


    function acl($oid, $rules = null)
    {
        if ($rules) {
            ow_delete(OW_ACL, $oid);

            foreach ($rules as $owner => $permission) {

                $data = compact('oid', 'owner', 'permission');

                ow_insert(OW_ACL, $data);
            }
        }
        else {
            return ow_select(OW_ACL, array('oid' => $oid));
        }
    }

    function get($oid)
    {
        $object = $this->fetch(array(OW_OBJECTS . ".oid = '$oid'"));

        if ($object) {
            return $object[0];
        }
        else {
            return null;
        }
    }


    function fetch($cond = array())
    {
        return ow_select($cond, $this->params);
    }



    function create($data = null, $owner = null, $metadata = array())
    {
        $oid = ow_oid();

        if(is_array($data)) {
            $data = json_encode($data);
        }

        // Won't cache metadata on the main table
        ow_insert(OW_OBJECTS, array('oid' => $oid, 'owner' => $owner, 'data' => $data));

        // Indexes and additional tables
        
        if (!empty($metadata['acl'])) {
            $this->acl($oid, $metadata['acl']);
            unset($metadata['acl']);
        }

        if (!empty($metadata['links'])) {
            // TODO processar links
            unset($metadata['links']);
        }

        foreach ($metadata as $meta_key => $meta_value) {

            if (is_array($meta_value)) {
                $meta_value = json_encode($meta_value);
            }

            $this->meta($oid, $meta_key, $meta_value);
        }

        if ($this->params['tables']) {

            foreach ($this->params['tables'] as $table => $fields) {

                $schema_data = array();

                foreach ($fields as $field) {

                    if (!empty($data[$field])) {
                        $schema_data[$field] = $data[$field];
                    }
                }

                if (!empty($schema_data)) {
                    $schema_data['oid'] = $oid;
                    ow_insert($table, $schema_data);
                }
            }
        }

        return $oid;
    }

    function write($oid, $data, $metadata = array())
    {

        ow_update(OW_OBJECTS, $oid, array('oid' => $oid, 'data' => $data));
        // TODO default acls devem vir do SCHEMA (db)


        // if versioning is on
        if ($this->params['versioning']) {
            ow_insert(OW_VERSION, array('oid' => $oid, 'data' => $data));
        }


        if (!$this->params['tables']) {
            return;
        }

        foreach ($this->params['tables'] as $table => $fields) {

            $schema_data = array();

            foreach ($fields as $field) {

                if (!empty($data[$field])) {
                    $schema_data[$field] = $data[$field];
                }
            }

            if (!empty($schema_data)) {
                ow_update($table, $oid, $schema_data);
            }
        }


        if (!empty($metadata['acl'])) {
            $this->acl($oid, $metadata['acl']);
        }


        if (!empty($params['index'])) {
            foreach ($params['index'] as $index_field) {
                if (!empty($data[$index_field])) {
                    ow_index($oid, $index_field, $data[$index_field]);
                }

            }
        }


        return $oid;
    }

    function delete($oid)
    {
        // TODO Implementar DELETE excluindo o OID de todas as tabelas (até OW_OBJECTS)
        throw new Exception('DELETE não implementado');
    }
}


// Filesystem


/**
 * Low-level SELECT Helper
 *
 * @param  $domain
 * @param  $cond
 * @param array $params
 * @return void
 */
function ow_select($cond, $params = array())
{

    global $db;

    // TODO rever params, não deveria passar o tables aqui ?
    // esse cara realmente sabe demais...
    $defaults = array(
        'fields' => '*',
        'tables' => array(),
        'order' => '',
        'acl' => array(),
        'iDisplayStart' => false,
        'iDisplayLength' => -1
    );

    $params = array_merge($defaults, $params);


    $query = "select {$params['fields']} from " . OW_OBJECTS;

    if ($params['tables']) {

        foreach (array_keys($params['tables']) as $joined) {
            $query .= " inner join $joined on " . OW_OBJECTS . ".oid = $joined.oid";
        }
    }

    if (!empty($cond['acl'])) {
        // TODO implementar ACL
        throw new Exception('ACL não implementado');
    }

    if (!empty($cond)) {
        // TODO usar prepared statements / escape()

        $query .= " where " . implode(" and ", $cond);

    }

    if (!empty($params['order'])) {
        // TODO if is array...
        $query .= " order by " . $params['order'];
    }

    // paging
    if ($params['iDisplayStart'] && $params['iDisplayLength'] != -1) {
        $query .= " limit " . $db->escape_string($params['iDisplayStart']) . ", " .
                  $db->escape_string($params['iDisplayLength']);
    }

    //echo $query;
    $result = $db->query($query);


    if ($result === FALSE) {
        throw new Exception($db->error);
    }

    $results = array();
    while ($data = $result->fetch_assoc()) {
        $results[] = $data;
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
 * The OID is generated
 *
 * @throws Exception
 * @param  $domain
 * @param  $oid
 * @param  $data
 * @return string
 */
function ow_insert($domain, $data)
{

    global $db;


    if (empty($data)) {
        respond(array("error" => 'Trying to write nothing'), 405);
    }
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


    $query = sprintf("insert into $domain (%s) values (%s)", implode(",", $query_fields), $values);


    if(defined('DEBUG')) {
        echo $query;
    }
    //print_r($bind_params);
    $stmt = $db->prepare($query);


    if ($stmt === FALSE) throw new Exception($db->error, 500);

    //print_r($bind_params);
    //print_r($stmt);
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

}


/**
 *
 * Low level SQL UPDATE helper
 *
 * @throws Exception on database errors
 * @param  $domain - The domain (table) to update
 * @param  $oid - The object's oid
 * @param  $data - Associative Array of updated data
 * @return The object's oid
 */
function ow_update($domain, $oid, $data)
{

    global $db;

    if (empty($oid)) throw new Exception('OID is required for UPDATEs', 405);

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

    $bind_params[0] .= 's';
    $bind_params[] = &$oid;


    $query = sprintf("update $domain set %s where oid = ?", implode(",", $query_args));

    $stmt = $db->prepare($query);


    if(defined('DEBUG')) {
        echo $query;
    }
    if ($stmt === FALSE) throw new Exception($db->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

    return $oid;
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

    if(defined('DEBUG')) {
        echo $query;

    }
    //echo $query;
    if ($stmt === FALSE) throw new Exception($db->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

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
