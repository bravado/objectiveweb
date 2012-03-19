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

    /*
     *
     * Array
(
    [id] => Array
        (
            [Field] => id
            [Type] => int(11) unsigned
            [Null] => NO
            [Key] => PRI
            [Default] =>
            [Extra] => auto_increment
        )

    [animal_id] => Array
        (
            [Field] => animal_id
            [Type] => int(11)
            [Null] => NO
            [Key] =>
            [Default] =>
            [Extra] =>
        )

    [anamnese] => Array
        (
            [Field] => anamnese
            [Type] => text
            [Null] => YES
            [Key] =>
            [Default] =>
            [Extra] =>
        )

    [created] => Array
        (
            [Field] => created
            [Type] => datetime
            [Null] => YES
            [Key] =>
            [Default] =>
            [Extra] =>
        )

)
     */
    var $fields = array();


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
    function select($params = array())
    {
        global $mysqli;

        //print_r($params); exit();
        $defaults = array(
            '_fields' => '*',
            '_op' => 'AND',
            '_join' => array(),
            '_offset' => null,
            '_limit' => null,
            '_page' => null
        );

        $params = array_merge($defaults, $params);

        // TODO sanitize _op (must be AND or OR)


        // fields
        // TODO escapar _fields
        $query = "select {$params['_fields']} from {$this->name}";

        // join
        // TODO escapar _join
        if (!empty($params['_join'])) {
            foreach ($params['_join'] as $join => $on) {
                $query .= " inner join $join on $on";
            }
        }

        // TODO usar prepared statements
        $conditions = array();
        foreach ($params as $k => $v) {

            if ($v[0] == '!') {
                $v = substr($v, 1);
                $_not = true;
            }
            else {
                $_not = false;
            }

            if ($k[0] != '_') {

                $key = $this->_cleanup_field($k);

                if ($v === NULL || $v == 'NULL') {
                    $conditions[] = sprintf("%s %s null", $key, $_not ? "is not" : "is");
                }
                else if (strpos($v, '%')) {
                    $conditions[] = sprintf("%s %s '%s'", $key,
                        $_not ? "not like" : "like",
                        $mysqli->escape_string($v));
                }
                else {
                    // TODO tratar >, <, >=, <=, <>
                    $conditions[] = sprintf("%s %s %s", $key,
                        $_not ? "!=" : "=",
                        is_numeric($v) ? $v : "'" . $mysqli->escape_string($v) . "'");

                }

            }
        }

        if (!empty($conditions)) {
            $query .= " where " . implode(" {$params['_op']} ", $conditions);
        }

        if (!empty($params['_order'])) {
            // TODO if is array...
            $query .= " order by " . $params['_order'];
        }

        //print_r($params); exit();

        debug($query);

        $result = $mysqli->query($query);

        if ($result === FALSE) {
            throw new Exception($mysqli->error, 500);
        }

        /* Get field information for all columns */
        $finfo = $result->fetch_fields();

        $results = array();
        if ($result->num_rows > 0) {
            while ($data = $result->fetch_row()) {
                // TODO verify/optimize for overlapping fields
                // This way SHOULD use the first table's field (not 100% sure)
                $r = array();
                for ($i = 0; $i < count($finfo); $i++) {
                    if (!isset($r[$finfo[$i]->name])) {
                        $r[$finfo[$i]->name] = $data[$i];
                    }
                }

                $results[] = $r;
            }
        }

        return $results;

    }

    function _cleanup_field($field)
    {
        global $mysqli;

        if (strpos($field, '.')) {
            $f = explode('.', $field);

            $key = null;
            foreach ($f as $k) {
                if($key) {
                    $key .= '.';
                }
                $key .= sprintf("`%s`", $mysqli->escape_string($k));
            }

            return $key;
        }
        else
        {
            return sprintf("`%s`", $mysqli->escape_string($field));
        }
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

        if (empty($data)) throw new Exception('Trying to write nothing', 405);

        $bind_params = array('');
        $query_fields = array();

        foreach ($data as $k => $v) {

            if (is_array($v)) { // TODO or is_class/is_object ?
                $data[$k] = json_encode($v);
            }

            $query_fields[] = "`$k`";

            if (is_bool($data[$k])) {
                $data[$k] = $data[$k] ? '1' : '0';
            }
            //            else if(is_float($data[$k])) {
            //                $bind_params[0] .= 'd';
            //            }
            //            else if(is_float($data[$k])) {
            //                $bind_params[0] .= 'd';
            //            }
            //            else {
            //                $bind_params[0] .= 's';
            //            }

            $bind_params[0] .= 's';
            $bind_params[] = &$data[$k];
        }

        $values = '?';
        for ($i = 1; $i < count($query_fields); $i++) {
            $values .= ',?';
        }

        $query = sprintf("insert into `$this->name` (%s) values (%s)", implode(",", $query_fields), $values);

        debug($query);

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

        if (empty($data)) throw new Exception('Trying to write nothing', 405);

        $bind_params = array('');
        $query_args = array();

        foreach ($data as $k => $v) {

            if (is_array($v)) { // TODO or is_class/is_object ?
                $data[$k] = json_encode($v);
            }

            $query_args[] = "`$k` = ?";
            // TODO tratar null ?
            $bind_params[0] .= 's';
            $bind_params[] = &$data[$k];
        }

        $key_args = array();
        foreach ($key as $k => $v) {
            $key_args[] = "`$k` = ?";
            // TODO tratar null ?
            $bind_params[0] .= 's';
            $bind_params[] = &$key[$k];
        }

        $query = sprintf("update $this->name set %s where %s", implode(",", $query_args), implode("AND", $key_args));

        debug($query);

        $stmt = $mysqli->prepare($query);


        if ($stmt === FALSE) throw new Exception($mysqli->error, 500);

        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

        $stmt->close();

        return $key;
    }


    function delete($conditions)
    {
        global $mysqli;

        if (!is_array($conditions)) {
            $conditions = array($this->pk => $conditions);
        }

        $query_args = array();
        $bind_params = array('');

        foreach ($conditions as $k => $v) {
            $query_args[] = "`$k` = ?";
            $bind_params[0] .= 's';
            $bind_params[] = &$conditions[$k];
        }

        $query = sprintf("delete from $this->name where %s", implode(' and ', $query_args));

        $stmt = $mysqli->prepare($query);

        debug($query);

        if ($stmt === FALSE) throw new Exception($mysqli->error, 500);

        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        if ($stmt->execute() === FALSE) throw new Exception($stmt->error, 500);

        $rows_deleted = $stmt->affected_rows;
        $stmt->close();

        return $rows_deleted;
    }
}

class TableStore extends OWHandler
{

    // Initialization parameters
    var $params = array();

    // The main table
    var $table = null;
    var $joins = array();

    // TODO associations
    var $hasMany = array();

    function init($params)
    {
        $defaults = array(
            'table' => $this->id,
            'extends' => null,
            'hasMany' => array()
        );

        $this->params = array_merge($defaults, $params);

        $this->hasMany = $this->params['hasMany'];

        if ($this->params['extends']) {
            $this->table = new Table($this->params['extends']);
            // Joined tables have the same primary key as their parent (mandatory)
            $this->joins[$this->params['table']] = sprintf("%s.%s = %s.%s", $this->params['extends'], $this->table->pk, $this->params['table'], $this->table->pk);
        }
        else {
            $this->table = new Table($this->params['table']);
        }


        //print_r($this); exit;

    }

    function get($oid)
    {
        $params = array('_op' => 'AND');
        if (is_array($oid)) {
            foreach ($oid as $k => $v) {
                $params[$k] = $v;
            }
        }
        else {
            $params["{$this->table->name}.{$this->table->pk}"] = $oid;
        }

        $result = $this->fetch($params);
        if (!empty($result)) {
            // Grab first result
            $result = $result[0];

            // Fetch relations
            foreach ($this->hasMany as $hasMany_id => $hasMany_params) {
                $hasMany_defaults = array(
                    'table' => $hasMany_id,
                    'key' => $this->table->name . "_id",
                    'join' => array()
                );

                $hasMany_params = array_merge($hasMany_defaults, $hasMany_params);

                $table = new Table($hasMany_params['table']);
                $select_params = array(
                    "{$table->name}.{$hasMany_params['key']}" => $result[$this->table->pk],
                    '_join' => $hasMany_params['join']
                );

                $result[$hasMany_id] = $table->select($select_params);

            }

            return $result;
        }
        else {
            return null;
        }
    }

    function fetch($params = array())
    {
        $params['_join'] = $this->joins;

        return $this->table->select($params);
    }

    function _insert_or_update_hasmany($data)
    {
        foreach ($this->hasMany as $hasMany => $hasMany_params) {
            if (!empty($data[$hasMany])) {
                foreach ($data[$hasMany] as $hasMany_data) {
                    $table = new Table($hasMany_params['table']);

                    $_delete = @$hasMany_data['_delete'];

                    // Only store relevant fields
                    $hasMany_data = array_intersect_key($hasMany_data, $table->fields);

                    // If has PK, update or delete
                    if (isset($hasMany_data[$table->pk])) {
                        if ($_delete) {
                            $table->delete($hasMany_data[$table->pk]);
                        }
                        else {
                            $update_cond = array("{$table->pk}" => $hasMany_data[$table->pk]);
                            $table->update($update_cond, $hasMany_data);
                        }

                    }
                    else {
                        // Insert new relation
                        // TODO should we really verify if _delete was set on an INSERT operation ?
                        if (!isset($hasMany_data['_delete'])) {
                            $hasMany_data[$hasMany_params['key']] = $data[$this->table->pk];
                            $table->insert($hasMany_data);
                        }

                    }

                }
            }
        }
    }

    function post($data = null)
    {
        global $mysqli;

        // Use transactions by default
        $mysqli->autocommit(false);

        // TODO autogenerate ID only if its char(36) (not using name as constraint)
        if ($this->table->pk == 'oid' && empty($data['oid'])) {
            $data['oid'] = ow_oid();
        }

        // Autogenerate 'created' and 'modified' fields if not provided
        if (!isset($data['created']) && isset($this->table->fields['created']) && $this->table->fields['created']['Type'] == 'datetime') {
            $data['created'] = now();
        }

        if (!isset($data['modified']) && isset($this->table->fields['modified']) && $this->table->fields['modified']['Type'] == 'datetime') {
            $data['modified'] = now();
        }

        // Filter relevant fields for this table
        $table_data = array_intersect_key($data, $this->table->fields);

        // If this domain extends another table, set the parent name on the discriminator column
        if (!empty($this->params['extends'])) {
            $table_data['_type'] = $this->id;
        }

        // Stores the inserted ID on the $data array
        $data[$this->table->pk] = $this->table->insert($table_data);

        // Store data on other tables
        foreach (array_keys($this->joins) as $joined) {
            $table = new Table($joined);

            $table_data = array_intersect_key($data, $table->fields);

            $table->insert($table_data);

        }

        $this->_insert_or_update_hasmany($data);


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

        return array($this->table->pk => $data[$this->table->pk]);
    }

    function put($oid, $data)
    {
        global $mysqli;

        if (isset($data[$this->table->pk])) {
            if ($data[$this->table->pk] != $oid) {
                throw new Exception(_('Cannot update the primary key, use rename instead'), 405);
            }
            else {
                unset($data[$this->table->pk]);
            }
        }

        $mysqli->autocommit(false);

        $cond = array($this->table->pk => $oid);

        // Update 'modified' field if not provided
        if (!isset($data['modified']) && isset($this->table->fields['modified']) && $this->table->fields['modified']['Type'] == 'datetime') {
            $data['modified'] = now();
        }

        // Filter relevant fields for this table
        $table_data = array_intersect_key($data, $this->table->fields);

        if (!empty($table_data)) {

            $this->table->update($cond, $table_data);
        }

        // Update data on other tables
        foreach (array_keys($this->joins) as $joined) {
            $table = new Table($joined);

            $table_data = array_intersect_key($data, $table->fields);

            if (!empty($table_data)) {
                $table->update($cond, $table_data);
            }

        }

        $data[$this->table->pk] = $oid;
        $this->_insert_or_update_hasmany($data);


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

    function delete($id)
    {
        global $mysqli;

        // Use transactions by default
        $mysqli->autocommit(false);

        // How many rows were deleted
        $affected_rows = 0;

        // Delete relations
        foreach ($this->hasMany as $hasMany_id => $hasMany_params) {
            $hasMany_defaults = array(
                'table' => $hasMany_id,
                'key' => $this->table->name . "_id",
                'join' => array()
            );

            $hasMany_params = array_merge($hasMany_defaults, $hasMany_params);

            $table = new Table($hasMany_params['table']);
            $delete_params = array(
                "{$hasMany_params['key']}" => $id
            );

            $affected_rows += $table->delete($delete_params);
        }

        // Delete the entity itself
        $table = new Table($this->params['table']);
        $affected_rows += $table->delete($id);

        // Delete parents (if exist)
        if ($this->params['extends']) {
            $table = new Table($this->params['extends']);
            $affected_rows += $table->delete($id);
        }

        $mysqli->commit();

        return array('delete' => $affected_rows);
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

    function init($params)
    {
        parent::init($params);

        // TODO check if all necessary tables exist (meta, versioning, etc)
    }

    function get($oid, $decoded = false)
    {
        $object = parent::get($oid);

        return $decoded ? json_decode($object['_content'], true) : $object['_content'];

    }

    function fetch($params)
    {
        // TODO só preciso trazer o _content nos fields!
        return parent::fetch($params);
    }

    function post($params)
    {
        // TODO apenas se o field OID for VARCHAR 36
        if (empty($params['oid'])) {
            $params['oid'] = ow_oid();
        }

        // index params (if fields exist)
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
        $data['_content'] = json_encode($params);

        return parent::post($data);
    }

    function put($oid, $data)
    {

        $object = parent::get($oid);

        // We will overwrite the dynamic content
        unset($object['_content']);

        // Now object has only valid fields, let's update those
        foreach (array_keys($object) as $k) {
            if (isset($data[$k])) {
                $object[$k] = $data[$k];
            }
        }

        // Final content will be everything on data + original fields
        // (dynamic content is ALWAYS OVERWRITTEN)
        $object['_content'] = json_encode(array_merge($data, $object));

        return parent::put($oid, $object);
    }
}


// Generic query function
function query($query)
{
    global $mysqli;

    debug($query);

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

