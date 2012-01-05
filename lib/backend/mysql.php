<?php
/**
 *
 * ObjectiveWeb
 * The default "mysql" backend
 *
 * Provides:
 *  TableStore - A handler that maps data to one or more SQL tables
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

$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if ($mysqli === FALSE) {
    throw new Exception($mysqli->error);
}

$mysqli->set_charset(OW_CHARSET);


class Table
{

    var $name = null;

    var $pk = null;
    var $fields = array();

    var $joins = array();

    function Table($name)
    {
        global $mysqli;

        $this->name = $mysqli->escape_string($name);


        $query = sprintf('describe `%s`', $this->name);
        $result = $mysqli->query($query);

        if ($result === FALSE || $result->num_rows == 0) {
            throw new Exception($mysqli->error, 500);
        }
        else {
            while ($data = $result->fetch_assoc()) {
                if ($data['Key'] == 'PRI') {
                    if ($this->pk != null) {
                        if (is_array($this->pk)) {
                            $this->pk[] = $data['Field'];
                        }
                        else {
                            $this->pk = array($this->pk, $data['Field']);
                        }
                    }
                    else {
                        $this->pk = $data['Field'];
                    }
                }

                $this->fields[$data['Field']] = $data;
            }

        }


    }

    /**
     * Low-level SELECT Helper
     *
     * @param  $cond
     * @param array $params
     * @return Array
     */
    function select($cond, $params = array())
    {
        global $mysqli;

        $defaults = array(
            'fields' => '*',
            'join' => array()
        );

        $params = array_merge($defaults, $params);

        $query = "select {$params['fields']} from {$this->name}";

        if(!empty($this->joins)) {
            foreach($this->joins as $join => $on) {
                $query .= " inner join $join on $on";
            }
        }

        if (!empty($cond)) {
            // TODO usar prepared statements / escape()

            if (!is_array($cond)) {
                $cond = explode('&', $cond);
            }

            $query .= " where " . implode(" and ", $cond);
        }

        if (!empty($params['order'])) {
            // TODO if is array...
            $query .= " order by " . $params['order'];
        }

        if (defined('DEBUG')) {
            error_log($query);
        }

        $result = $mysqli->query($query);

        if ($result === FALSE) {
            throw new Exception($mysqli->error, 500);
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
     * Low level SQL INSERT helper
     *
     * @throws Exception
     * @param  $data
     * @return string
     */
    function insert($data)
    {
        global $mysqli;

        if (empty($data)) throw new Exception( 'Trying to write nothing', 405);

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

        $query = sprintf("insert into `$this->name` (%s) values (%s)", implode(",", $query_fields), $values);

        if (defined('DEBUG')) {
            error_log($query);
        }

        $stmt = $mysqli->prepare($query);

        if ($stmt === FALSE) throw new Exception($mysqli->error, 500);

        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

        $stmt->close();


        if (empty($data[$this->pk])) {
            // TODO só retornar insert_id se for auto_increment
            return $mysqli->insert_id;
        }
        else {
            return $data[$this->pk];
        }

    }

    /**
     *
     * Low level SQL UPDATE helper
     *
     * @throws Exception on database errors
     * @param  $cond - Perform update on rows that match these conditions
     * @param  $data - Associative Array of updated data
     * @return The object's oid
     */
    function update($key, $data)
    {

        global $mysqli;

        if (empty($key)) throw new Exception('A condition is required for UPDATEs', 405);

        if (empty($data)) throw new Exception( 'Trying to write nothing', 405);

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

        $query = sprintf("update $this->name set %s where %s", implode(",", $query_args), implode("AND", $key_args));

        if (defined('DEBUG')) {
            error_log($query);
        }

        $stmt = $mysqli->prepare($query);


        if ($stmt === FALSE) throw new Exception($mysqli->error, 500);

        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

        $stmt->close();

        return $key;
    }


    function join($table, $on) {
        $this->joins[$table] = $on;
    }
}

class TableStore extends OWHandler
{

    // The main table
    var $table = null;

    // TODO associations
    var $hasMany = array();
    var $belongsTo = array();

    var $_inherits = false;

    function init($params)
    {
        global $mysqli;

        $table = isset($params['table']) ? $params['table'] : $this->id;
        if (isset($params['extends'])) {
            $this->_inherits = true;
            $this->table = new Table($params['extends']);
            // Joined tables have the same primary key as their parent (mandatory)
            $this->table->join($table, sprintf("%s.%s = %s.%s", $params['extends'], $this->table->pk, $table, $this->table->pk));
        }
        else {
            $this->table = new Table($table);
        }

        //print_r($this); exit;

    }

    function get($oid, $params = array())
    {
        // TODO support compound keys
        $result = $this->fetch(sprintf("%s.%s = '%s'", $this->table->name, $this->table->pk, $oid), $params);
        if (!empty($result)) {
            return $result[0];
        }
        else {
            return null;
        }
    }

    function fetch($cond = array(), $params = array())
    {
        return $this->table->select($cond, $params);
    }

    function create($data = null)
    {
        global $mysqli;

        $mysqli->autocommit(false);
        // TODO autogenerate ID if its char(36)
        if ($this->table->pk == 'oid' && empty($data['oid'])) {
            $data['oid'] = ow_oid();
        }

        // Filter relevant fields for this table
        $table_data = array_intersect_key($data, $this->table->fields);

        // TODO marcar o DTYPE da tabela mãe em $data
        if(!empty($this->table->joins)) {
            $table_data['_type'] = $this->id;
        }

        $data[$this->table->pk] = $this->table->insert($table_data);

        // Store data on other tables
        foreach(array_keys($this->table->joins) as $joined) {
            $table = new Table($joined);

            $table_data = array_intersect_key($data, $table->fields);

            $table->insert($table_data);

        }

        $mysqli->commit();

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

        return $data[$this->table->pk];
    }

    function write($oid, $data)
    {
        global $mysqli;

        $mysqli->autocommit(false);

        $cond = array($this->table->pk => $oid);

        // Filter relevant fields for this table
        $table_data = array_intersect_key($data, $this->table->fields);

        if(!empty($table_data)) {

            $this->table->update($cond, $table_data);
        }

        // Store data on other tables
        foreach(array_keys($this->table->joins) as $joined) {
            $table = new Table($joined);

            $table_data = array_intersect_key($data, $table->fields);

            if(!empty($table_data)) {
                $table->update($cond, $table_data);
            }

        }

        $mysqli->commit();

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

    }

    function delete($oid)
    {
        // TODO Implementar DELETE excluindo o OID de todas as tabelas auxiliares
        throw new Exception('DELETE não implementado');
    }

    function has_field($field)
    {
        return isset($this->table->fields[$field]);
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

        // TODO check if all necessary tables exist (meta, versioning, etc)
    }

    function schema($name)
    {

    }

    function get($oid, $decoded = false)
    {
        $object = parent::get($oid);

        return $decoded ? json_decode($object['_content'], true) : $object['_content'];

    }

    function fetch($cond)
    {
        // TODO só preciso trazer o content de field!
        return parent::fetch($cond);
    }

    function create($params)
    {

        if (empty($params['oid'])) {
            $params['oid'] = ow_oid();
        }

        if (empty($params['schema'])) {
            $params['schema'] = 'ow/data';
        }

        foreach (array_keys($params) as $k) {
            if ($this->has_field($k)) {
                $data[$k] = $params[$k];
            }
        }

        // TODO lang (if lang exists)
        // $data['lang'] = null;

        // TODO geolocation (if lat, long exists)

        // TODO hierarchy (if left, right exists)


        // Parameters are encoded as json
        // TODO index params (if indexed)
        $data['_content'] = json_encode($params);

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
        foreach (array_keys($object) as $k) {
            if (isset($data[$k])) {
                $object[$k] = $data[$k];
            }
        }

        // Final content will be everything on data + original fields
        // (dynamic content is ALWAYS OVERWRITTEN)
        $object['_content'] = json_encode(array_merge($data, $object));

        return parent::write($oid, $object);
    }
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
    global $mysqli;

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

    $stmt = $mysqli->prepare($query);

    if (defined('DEBUG')) {
        echo $query;

    }
    //echo $query;
    if ($stmt === FALSE) throw new Exception($mysqli->error, 500);

    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

    $stmt->close();

}


// Filesystem
function query($query)
{
    global $mysqli;

    $result = $mysqli->query($query);


    if ($result === FALSE) {
        throw new Exception($mysqli->error);
    }

    $results = array();

    if ($result->num_rows > 0) {

        while ($data = $result->fetch_assoc()) {
            $results[] = $data;
        }

    }

    return $results;

}

