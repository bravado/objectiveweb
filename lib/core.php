<?php
/**
 * ObjectiveWeb
 *
 * Platform Core
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:09
 */

global $db;

// Default db config
defined('MYSQL_HOST') or define('MYSQL_HOST', 'localhost');
defined('MYSQL_USER') or define('MYSQL_USER', 'objectiveweb');
defined('MYSQL_PASS') or define('MYSQL_PASS', null);
defined('MYSQL_DB') or define('MYSQL_DB', 'objectiveweb');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8');

// Database Tables
defined('OW_INDEX') or define('OW_INDEX', 'ow_index');
defined('OW_VERSION') or define('OW_VERSION', 'ow_version');
defined('OW_LINKS') or define('OW_LINKS', 'ow_links');
defined('OW_ACL') or define('OW_ACL', 'ow_acl');
defined('OW_SEQUENCE') or define('OW_SEQUENCE', 'ow_sequence');


$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);


if ($db === FALSE) {
    throw new Exception($db->error);
}
// TODO tratar erro de conexão de db e devolver headers e mensagem certas (protocolo)
// Algo como send_error(new Exception($db->error));

$db->set_charset(DB_CHARSET);


/**
 * Writes an Object to a domain
 *
 * @param  $domain The target domain
 * @param  $key The object's key on the domain
 * @param null $data The object's data
 * @param array $params The write parameters
 * @return string|The object's oid. If the object didn't exist, a new oid is created
 */
function ow_write($domain, $key, $data = null, $params = array())
{
    $defaults = array(
        'key' => 'id',
        'versioning' => false,
        'join' => array(),
        'index' => array(),
        'acl' => array()
    );

    $params = array_merge($defaults, $params);

    $orig_data = $data;

    // joined tables
    $join = array();
    if (!empty($params['join'])) {
        //
        // ['join'] = array (
        //   'one_table' => array('field1', 'field2, 'field3'),
        //   'another_table' => array('field4, field5') );
        //
        foreach ($params['join'] as $table => $fields) {
            $join[$table] = array();
            foreach ($fields as $field) {

                if (!empty($data[$field])) {
                    $join[$table][$field] = $data[$field];
                    unset($data[$field]);
                }

            }

            if (empty($join[$table])) {
                unset($join[$table]);
            }
        }
    }

    $data[$params['key']] = $key;

    $oid = ow_oid($domain, $key, $params['key']);

    if ($oid == null) {
        $oid = ow_insert($domain, $oid, $data);

        foreach ($join as $table => $data) {
            ow_insert($table, $oid, $data);
        }
    }
    else {
        ow_update($domain, $oid, $data);

        foreach ($join as $table => $data) {
            ow_update($table, $oid, $data);
        }
    }

    if (!empty($params['acl'])) {
        foreach ($params['acl'] as $acl_oid => $acl_flags) {
            ow_acl($oid, $acl_oid, $acl_flags);
        }
    }


    if (!empty($params['index'])) {
        foreach ($params['index'] as $index_field) {


            if (!empty($data[$index_field])) {
                ow_index($oid, $index_field, $data[$index_field]);
            }

        }
    }

    // if versioning is on
    if ($params['versioning']) {
        ow_insert(OW_VERSION, $oid, array('content' => $orig_data));
    }

    return $oid;
}


/**
 * Reads an object from a domain given its key
 *
 * @param  $domain
 * @param  $key
 * @return void
 */
function ow_read($domain, $key, $params = array())
{

    $defaults = array(
        'key' => 'id'
    );

    $params = array_merge($defaults, $params);

    return ow_select($domain, array($params['key'] => $key), $params);
}


/**
 *
 * Returns an Object Identifier (oid) for the requested id on a given domain.
 *
 * @throws Exception on database errors
 * @param  $domain - The requested domain
 * @param  $key - The requested key
 * @param string $id_field - The id field on the database (defaults to "id")
 * @return The object's oid or null if the object does not exist
 */
function ow_oid($domain, $key, $key_field = 'id')
{
    global $db;
    $oid = null;

    $stmt = $db->prepare("select oid from $domain where $key_field = ?");

    if ($stmt === FALSE) throw new Exception($stmt->error);

    $stmt->bind_param('s', $key);


    if ($stmt->execute() === FALSE) throw new Exception($stmt->error);

    $stmt->bind_result($oid);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
    }

    $stmt->close();

    return $oid;

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
 * Manipulates an object's Access Control List
 *
 * @param  $oid The object's id
 * @param int $flags If specified, update the ACL. Otherwise, returns the object's ACL array.
 * @return void
 */
function ow_acl($oid, $owner, $flags = null)
{

    if ($flags) {
        // TODO INSERT on duplicate key UPDATE...

        return $flags;
    }
    else {
        // TODO SELECT * FROM ow_permissions WHERE oid = $oid


    }

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
 * Retrieve an object from a domain given its OID
 *
 * @param  $domain
 * @param  $oid
 * @param array $params
 * @return void
 */
function ow_get($domain, $oid, $params = array())
{

    return ow_select($domain, array('oid' => $oid), $params);

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


/**
 * Low level SQL SELECT helper
 *
 * @param  $domain
 * @param  $cond
 * @param array $params
 * @return void
 */
function ow_select($domain, $cond, $params = array())
{

    global $db;

    $defaults = array(
        'fields' => '*',
        'join' => array(),
        'order' => '',
        'acl' => array()
    );

    $params = array_merge($defaults, $params);


    $query = "select {$params['fields']} from {$domain}";

    if (!empty($params['join'])) {
        // TODO implementar JOIN
        throw new Exception('JOIN não implementado');
    }


    if (!empty($params['acl'])) {
        // TODO implementar ACL
        throw new Exception('ACL não implementado');
    }


    if (!empty($cond)) {
        // TODO usar prepared statements / escape()

        $query .= " where ".implode(" and ", $cond);

    }


    if(!empty($params['order'])) {
        // TODO if is array...
        $query .= " order by ".$params['order'];
    }


    $result = $db->query($query);


    if($result === FALSE) {
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
 * @throws Exception
 * @param  $domain
 * @param  $oid
 * @param  $data
 * @return string
 */
function ow_insert($domain, $oid, $data)
{

    global $db;

    $bind_params = array('');
    $query_fields = array();

    foreach ($data as $k => $v) {

        if (is_array($v)) { // TODO or is_class/is_object ?
            $data[$k] = json_encode($v);
        }

        $query_fields[] = $k;
        $bind_params[0] .= 's';
        $bind_params[] = &$data[$k];
    }

    $query_fields[] = 'oid';
    $bind_params[0] .= 's';
    $bind_params[] = &$oid;


    if ($oid == null) {
        $oid = uniqid();
    }

    $values = '?';
    for ($i = 1; $i < count($query_fields); $i++) {
        $values .= ',?';
    }

    $query = sprintf("insert into $domain (%s) values (%s)", implode(",", $query_fields), $values);

    $stmt = $db->prepare($query);


    if ($stmt === FALSE) throw new Exception($db->error);

    //print_r($bind_params);
    //print_r($stmt);
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error);

    $stmt->close();

    return $oid;
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

    if (empty($oid)) throw new Exception('OID is required for UPDATEs');


    $bind_params = array('');
    $query_args = array();


    foreach ($data as $k => $v) {

        if (is_array($v)) { // TODO or is_class/is_object ?
            $data[$k] = json_encode($v);
        }

        $query_args[] = "$k = ?";
        $bind_params[0] .= 's';
        $bind_params[] = &$data[$k];
    }

    $bind_params[0] .= 's';
    $bind_params[] = &$oid;


    $query = sprintf("update $domain set %s where oid = ?", implode(",", $query_args));

    $stmt = $db->prepare($query);

    echo $query;
    if ($stmt === FALSE) throw new Exception($db->error);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error);

    $stmt->close();

    return $oid;
}

