<?php
/**
 *
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

$db->set_charset(DB_CHARSET);

class MysqlDomain
{
    var $domain;
    var $params;

    function MysqlDomain($domain, $params)
    {
        $this->domain = $domain;
        $this->params = $params;
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
        // ACL é tratada dentro de fetch
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
        // TODO tratar ACL CURRENT USER

        $params = array(
            'acl' => array()
        );

        $params = array_merge($this->params, $params);

        return ow_select($cond, $params);
    }


    /**
     * Creates a new Object on a domain
     *
     * @param string $domain The target domain
     * @param null $data The object's data
     * @return string|The object's new oid.
     */
    function create($data = null, $metadata = array())
    {
        $current_user = current_user();

        // TODO de alguma forma tenho que saber quem está logado!
        // este arquivo inteiro é um driver

        // TODO só ROOT pode definir OWNER

        // TODO pode ter mais metadados, como created, ip de origem, etc
        $defaults = array(
            'acl' => array($current_user['oid'] => 1)
        );

        $metadata = array_merge($defaults, $metadata);

        $oid = ow_oid();
        ow_insert(OW_OBJECTS, array('oid' => $oid));


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

        if (!empty($metadata['acl'])) {
            ow_acl($oid, $metadata['acl']);
        }

        return $oid;
    }

    /**
     * Writes data to an existing object
     * @param  $oid
     * @param Array $data
     * @param Array $metadata
     * @return array The changed data
     */
    function write($oid, $data, $metadata = array())
    {

        $current_user = current_user();

        // TODO verificar se pode atualizar este oid

        // TODO campos da tabela OW_OBJECTS (todo mundo extende object, é obrigatório

        // TODO default acls devem vir do SCHEMA (db)

        // TODO OW_SCHEMA É UM DOMAIN (MySQL)

        // TODO OW_ACL É UM DOMAIN (MySQL)

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
            foreach ($metadata['acl'] as $acl_oid => $acl_flags) {
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
            ow_insert(OW_VERSION, array('oid' => $oid, 'content' => $data, 'metadata' => $metadata));
        }

        return $oid;
    }


    function delete($oid) {
        // TODO Implementar DELETE excluindo o OID de todas as tabelas (até OW_OBJECTS)
        throw new Exception('DELETE não implementado');
    }
}


// Filesystem
/**
 * Manipulates an object's Access Control List
 *
 * @param  $oid string The object's id
 * @param Array $acl If specified, update the ACL. Otherwise, returns the object's ACL array.
 * @return void
 */
function ow_acl($oid, $acl = null)
{
    if ($acl) {
        ow_delete(OW_ACL, $oid);

        foreach ($acl as $owner => $permission) {

            $data = compact('oid', 'owner', 'permission');

            ow_insert(OW_ACL, $data);
        }
    }
}


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

    foreach (array_keys($params['tables']) as $joined) {
        $query .= " inner join $joined on " . OW_OBJECTS . ".oid = $joined.oid";
    }

    if (!empty($params['acl'])) {
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

    //echo $query;
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

    //echo $query;
    if ($stmt === FALSE) throw new Exception($db->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

    return $oid;
}

function ow_delete($table, $oid)
    {
        global $db;

        // TODO suportar array de condições em $oid, juntar com AND

        $bind_params = array('s', &$oid);

        $stmt = $db->prepare("delete from $table where oid = ?");

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
