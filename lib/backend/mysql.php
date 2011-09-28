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


$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);


if ($db === FALSE) {
    throw new Exception($db->error);
}

$db->set_charset(DB_CHARSET);


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
