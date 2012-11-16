<?php
/**
 *
 * ObjectiveWeb
 * The default "mysql" backend
 *
 * Provides:
 *  Table - Low-level OO representation of a table with insert/select/delete/update methods
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

// Generic query function
function query() {
    global $mysqli;

    $query = call_user_func_array('sprintf', func_get_args());

    debug($query);

    $result = $mysqli->query($query);

    if ($result === FALSE) {
        throw new Exception($mysqli->error);
    }

    /* Get field information for all columns */
    $finfo = $result->fetch_fields();

    $results = array();
    if ($result->num_rows > 0) {
        while ($data = $result->fetch_row()) {
            $r = array();
            for ($i = 0; $i < count($finfo); $i++) {
                $_field = $finfo[$i]->name;
                $_dot = strpos($_field, '.');
                if ($_dot !== FALSE) {
                    $_e = substr($_field, 0, $_dot);
                    $_f = substr($_field, $_dot + 1);

                    if (!isset($r[$_e])) {
                        $r[$_e] = array($_f => $data[$i]);
                    }
                    else {
                        if (!isset($r[$_e][$_f])) {
                            $r[$_e][$_f] = $data[$i];
                        }
                    }
                }
                else {
                    // Never overwrite a field
                    // This way we use the first table's field
                    if (!isset($r[$finfo[$i]->name])) {
                        $r[$finfo[$i]->name] = $data[$i];
                    }
                }
            }

            $results[] = $r;
        }
    }

    return $results;

}

function tablestore_page($args, $params = array()) {
    if ($args) {
        // Page
        isset($params["_limit"]) || $params["_limit"] = 20;
        $page = intval($args);

        $params['_offset'] = ($page ? $page - 1 : 0) * $params['_limit'];

        return $params;
    }
    else {
        $params['_fields'] = array(
            'total' => 'COUNT(*)'
        );

        return $params;
    }
}

class Table {

    var $name = null;
    var $pk = null;

    var $filters = array();
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

    function Table($name) {
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
     * Lists this table's fields
     * @return array with all the table field names
     */
    function fields() {
        return array_keys($this->fields);
    }

    function filter($key, $callback) {
        if (!isset($this->filters[$key])) {
            $this->filters[$key] = array($callback);
        }
        else {
            $this->filters[$key][] = $callback;
        }
    }

    function apply_filter($key, $content) {
        if (isset($this->filters[$key])) {
            foreach ($this->filters[$key] as $callback) {
                $content = call_user_func($callback, $content);
            }
        }

        return $content;
    }

    /**
     * Low-level SELECT Helper
     *
     * @param  $cond
     * @param array $params
     * @return Array
     */
    function select($params = array()) {
        global $mysqli;

        $defaults = array(
            '_fields' => '*',
            '_op' => 'AND',
            '_inner' => array(),
            '_left' => array(),
            '_offset' => 0,
            '_limit' => null,
            '_group' => null,
            '_order' => null
        );

        $params = array_merge($defaults, $params);


        // Fields
        $_fields = '';
        if (is_string($params['_fields'])) {
            $params['_fields'] = explode(",", $params['_fields']);
        }

        $i = 0;
        foreach($params['_fields'] as $alias => $_field) {
            $_fields .= ($i++ > 0 ? ',' : '');

            if($_field != '*') {
                $_field = $this->_cleanup_field($_field)." as `".(is_numeric($alias) ? $_field : $alias)."`";
            }

            $_fields .= $_field;
        }

        $_fields = $this->apply_filter('fields', $_fields);
        $query = "select {$_fields} from `{$this->name}`";

        // TODO sanitize _op (must be AND or OR)

        // joins
        // TODO escape _join ON conditions
        foreach (array('inner', 'left') as $i) {
            foreach ($params["_$i"] as $join => $on) {

                if (is_array($on)) {
                    $query .= " $i join `{$on['table']}` as `$join` on {$on['on']}";
                }
                else {
                    $query .= " $i join `$join` on $on";
                }
            }
        }

        $conditions = array();
        foreach ($params as $k => $v) {

            if ($k[0] != '_') {

                if(is_array($v)) {
                    foreach($v as $k1 => $v1) {

                        // TODO verify, group same fields on ( ), consider _op for a single field
                        if(is_numeric($k1)) {
                            $key = $this->_cleanup_field($k);
                        }
                        else {
                            $key = $this->_cleanup_field("$k.$k1");
                        }

                        $conditions[] = $this->_parse_condition("$key", $v1);
                    }
                }
                else {
                    $key = $this->_cleanup_field($k);
                    $conditions[] = $this->_parse_condition($key, $v);
                }

            }
        }

        if (!empty($conditions)) {
            $query .= " where " . implode(" {$params['_op']} ", $conditions);
        }


        if (!empty($params['_order'])) {
            if (is_string($params['_order'])) {
                $params['_order'] = array($params['_order']);
            }
            $_order = '';
            for ($i = 0; $i < count($params['_order']); $i++) {
                // TODO parse/escape _order
                $_order .= ($i > 0 ? ',' : '') . "{$params['_order'][$i]}";
            }
            $query .= " order by $_order";
        }


        if (!empty($params['_limit'])) {
            $query .= sprintf(" limit %d,%d", $params['_offset'], $params['_limit']);
        }

        return query($query);

    }

    function _parse_condition($key, $v) {
        global $mysqli;

        if ($v[0] == '!') {
            $v = substr($v, 1);
            $_not = true;
        }
        else {
            $_not = false;
        }

        if ($v === NULL || $v == 'NULL') {
            return sprintf("%s %s null", $key, $_not ? "is not" : "is");
        }
        else if (strpos($v, '%') !== FALSE) {
            return sprintf("%s %s '%s'", $key,
                $_not ? "not like" : "like",
                $mysqli->escape_string(str_replace('%', '%%',$v)));
        }
        else {
            $_gt = false;
            $_lt = false;
            $_equal = false;
            do {

                switch($v[0]) {
                    case '>':
                        $_gt = true;
                        $v = substr($v, 1);
                        break;
                    case '<':
                        $_lt = true;
                        $v = substr($v, 1);
                        break;
                    case '=':
                        $v = substr($v, 1);
                        $_equal = true;
                    default:
                        break;
                }
            } while($v[0] && strpos('><=', $v[0]) !== FALSE);

            $_op = $_gt ? ($_equal ? '>=' : '>') : ($_lt ? ($_equal ? '<=' : '<') : ($_not ? "!=" : "=") );


            // TODO tratar >, <, >=, <=, <>
            return sprintf("%s %s %s", $key,
                $_op,
                is_numeric($v) ? $v : "'" . $mysqli->escape_string($v) . "'");

        }
    }

    function _cleanup_field($field) {
        global $mysqli;
        // verifica se $field não é uma function - a-zA-Z\(.*\)
        if(preg_match('/[a-zA-Z]+\(.*\)/', $field)) {
            return $field;
        }
        elseif (strpos($field, '.')) {
            $f = explode('.', $field);

            $key = null;
            foreach ($f as $k) {
                if ($key) {
                    $key .= '.';
                }
                $key .= sprintf("`%s`", $mysqli->escape_string($k));
            }

            return $key;
        }
        else {
            return sprintf("`{$this->name}`.`%s`", $mysqli->escape_string($field));
        }
    }

    /**
     * Low level SQL INSERT helper
     *
     * @throws Exception
     * @param  $data
     * @return string
     */
    function insert($data) {
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

        if ($stmt->execute() === FALSE) {
            switch ($stmt->errno) {
                case 1062: // Error: 1062 SQLSTATE: 23000 (ER_DUP_ENTRY)
                    throw new Exception($stmt->error, 409);
                default:
                    throw new Exception($stmt->errno . ' ' . $stmt->error, 500);
            }
        }

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
    function update($key, $data) {

        global $mysqli;

        if (empty($key)) throw new Exception('A condition is required for UPDATEs', 405);

        if (empty($data)) throw new Exception('Trying to write nothing', 405);

        $bind_params = array('');
        $query_args = array();

        foreach (array_keys($data) as $k) {

            if (is_array($data[$k])) { // TODO or is_class/is_object ?
                $data[$k] = json_encode($data[$k]);
            }
            elseif(is_bool($data[$k])) {
                $data[$k] = $data[$k] ? '1' : '0';
            }

            if($data[$k] === NULL || $data[$k] == 'NULL') {
                $query_args[] = "`$k` = NULL";
            }
//            TODO elseif(preg_match('/[a-zA-Z]+\(.*\)/', $data[$k])) {
//                $query_args[] = "`$k` = {$data[$k]}";
//            }
            else {
                $query_args[] = "`$k` = ?";
                $bind_params[0] .= 's';
                $bind_params[] = &$data[$k];
            }
        }

        $key_args = array();
        foreach ($key as $k => $v) {
            if($v == NULL || $v == 'NULL') {
                $key_args[] = "`$k` is NULL";
            }
            else {
                $key_args[] = "`$k` = ?";
                // TODO tratar null ?
                $bind_params[0] .= 's';
                $bind_params[] = &$key[$k];
            }
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


    function delete($conditions) {
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

class TableStore extends OWHandler {

    // Initialization parameters
    var $params = array();

    // The main table
    var $table = null;
    var $joins = array();

    // associations
    var $hasOne = array();
    var $hasMany = array();
    var $belongsTo = array();

    function init($params) {
        $defaults = array(
            'table' => $this->id,
            'extends' => null,
            'hasOne' => array(),
            'hasMany' => array(),
            'belongsTo' => array(),
            'views' => array(
                'page' => 'tablestore_page'
            )
        );

        $this->params = array_merge($defaults, $params);

        $this->hasOne = $this->params['hasOne'];
        $this->hasMany = $this->params['hasMany'];
        $this->belongsTo = $this->params['belongsTo'];

        if ($this->params['extends']) {
            $this->table = new Table($this->params['extends']);
            // Joined tables have the same primary key as their parent (mandatory)
            $this->joins[$this->params['table']] = sprintf("%s.%s = %s.%s", $this->params['extends'], $this->table->pk, $this->params['table'], $this->table->pk);
        }
        else {
            $this->table = new Table($this->params['table']);
        }


    }

    function get($oid, $params = array()) {

        // Accept params in querystring form
        if (!is_array($params)) {
            $arr = array();
            parse_str($params, $arr);
            $params = $arr;
        }

        if (is_array($oid)) {
            foreach ($oid as $k => $v) {
                $params[$k] = $v;
            }
        }
        else {
            $params["{$this->table->name}.{$this->table->pk}"] = $oid;
        }

        $defaults = array(
            '_eager' => true
        );

        $params = array_merge($defaults, $params);
        $result = $this->fetch($params);
        if (!empty($result)) {
            // Grab first result
            $result = $result[0];

            // If eager and pk is set, fetch relations
            // (pk can be excluded from the _fields parameter)
            if ($params['_eager'] && isset($result[$this->table->pk])) {
                foreach ($this->hasMany as $hasMany_id => $hasMany_params) {
                    $hasMany_defaults = array(
                        'table' => $hasMany_id,
                        'key' => $this->table->name . "_id",
                        'join' => array()
                    );

                    $hasMany_params = array_merge($hasMany_defaults, $hasMany_params);

                    $table = new TableStore();
                    $table->init($hasMany_params);
                    $select_params = array(
                        "{$hasMany_params['key']}" => $result[$this->table->pk]
                    );

                    $result[$hasMany_id] = $table->fetch($select_params);

                }


            }

            return $result;
        }
        else {
            return null;
        }
    }

    function fetch($params = array()) {

        // Accept params in querystring form
        if (!is_array($params)) {
            $arr = array();
            parse_str($params, $arr);
            $params = $arr;
        }

        $defaults = array(
            '_fields' => null,
            '_eager' => true,
            '_inner' => array()
        );

        $params = array_merge($defaults, $params);

        // Table Inheritance
        if(!empty($this->joins)) {
            foreach($this->table->fields() as $_field) {
                $_fields[$_field] = $this->table->name.'.'.$_field;
            }
            foreach ($this->joins as $join => $on) {

                $join_table = new Table($join);
                // Keep fields from the first table
                foreach($join_table->fields() as $_field) {
                    $_fields[$_field] = "$join.$_field";
                }

                //$_fields = array_merge($join_table->fields(), $_fields);
                $params['_inner'][$join] = array('table' => $join, 'on' => $on);

            }

        }
        else {
            $_fields = $this->table->fields();
        }

        if ($params['_eager']) {
            // TODO _eager pode ser a lista de relações que pode pegar
            // ex: post _eager=comments,votes

            foreach ($this->belongsTo as $k => $v) {

                $belongsTo_table = new Table($v['table']);

                $belongsTo_fields = isset($v['fields']) ? $v['fields'] : $belongsTo_table->fields();

                foreach ($belongsTo_fields as $f) {
                    $_fields[] = "$k.$f";
                }

                $belongsTo_join = null;
                if(is_array($v['key'])) {
                    $cond = array();
                    foreach($v['key'] as $field => $value) {

                        if(in_array($value, $this->table->fields())) {
                            // $value is a field on the main table
                            $cond[] = sprintf("`%s`.`%s` = `%s`.`%s`",
                                $k, $field,
                                $this->table->name, $value);
                        }
                        else {
                            $cond[] = sprintf("`%s`.`%s` = %s",
                                $k, $field,
                                $value === NULL ? 'NULL' : "'$value'"
                            );
                        }

                    }

                    $belongsTo_join = implode(" AND ", $cond);
                }
                else {
                    $belongsTo_join = sprintf("`%s`.`%s` = `%s`.`%s`", $k, $belongsTo_table->pk, isset($v['from']) ? $v['from'] : $this->table->name, $v['key']);
                }

                $params['_inner'][$k] = array(
                    'table' => $v['table'],
                    'on' => $belongsTo_join

                );
            }

            foreach($this->hasOne as $k => $v) {

                $hasOne_table = new Table($v['table']);

                $hasOne_fields = isset($v['fields']) ? $v['fields'] : $hasOne_table->fields();

                foreach ($hasOne_fields as $f) {
                    $_fields[] = "$k.$f";
                }

                $params['_left'][$k] = array(
                    'table' => $v['table'],
                    'on' => sprintf("`%s`.`%s` = `%s`.`%s`", $k, $hasOne_table->pk, isset($v['from']) ? $v['from'] : $this->table->name, $v['key'])
                );

            }
        }

        if (!$params['_fields']) {
            $params['_fields'] = $_fields;
        }

        return $this->table->select($params);
    }

    function _insert_or_update_hasmany($data) {
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

    function post($data = null) {
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

    function put($oid, $data) {
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

        $cond = is_array($oid) ? $oid : array($this->table->pk => $oid);

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

        return array($this->table->pk => $oid);
    }

    function delete($id) {
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

    function has_field($field) {
        return isset($this->table->fields[$field]);
    }
}


/**
 * ObjectStore
 *
 */
class ObjectStore extends TableStore {

    function init($params) {
        parent::init($params);

        // TODO check if all necessary tables exist (meta, versioning, etc)
    }

    function get($oid) {
        $object = parent::get($oid);
        return $object;

        //return $decoded ? json_decode($object['_content'], true) : $object['_content'];

    }

    function fetch($params) {
        // TODO só preciso trazer o _content nos fields!
        $results = parent::fetch($params);

        $entries = array();
        for ($i = 0; $i < count($results); $i++) {
            if ($results[$i]['_content']) {
                $results[$i] = array_merge($results[$i], json_decode($results[$i]['_content'], true));
            }
            unset($results[$i]['_content']);
        }

        return $results;
    }

    function post($params) {
        $return = array();

        // TODO apenas se o field OID for VARCHAR 36
        if ($this->has_field('oid')) {
            if (empty($params['oid'])) {
                $params['oid'] = ow_oid();
            }

            $return['oid'] = $params['oid'];
        }

        // index params (if fields exist)
        foreach (array_keys($params) as $k) {
            if ($this->has_field($k)) {
                $data[$k] = $params[$k];
                unset($params[$k]);
            }
        }

        // TODO lang (if lang exists)
        // $data['lang'] = null;

        // TODO geolocation (if lat, long exists)

        // TODO hierarchy (if left, right exists)


        // Parameters are encoded as json
        $data['_content'] = json_encode($params);

        return array_merge($return, parent::post($data));

    }

    function put($oid, $data) {

        $object = $this->get($oid);

        $content = array();
        foreach (array_keys($object) as $k) {
            if (isset($data[$k])) {
                $object[$k] = $data[$k];
                unset($data[$k]);
            }

            if ($this->has_field($k)) {
                $content[$k] = $object[$k];
                unset($object[$k]);
            }
        }

        foreach (array_keys($data) as $k) {
            if (!isset($object[$k])) {
                $object[$k] = $data[$k];
            }
        }

        // Final content will be everything on data + original fields
        $content['_content'] = json_encode($object);

        return parent::put($oid, $content);
    }
}
