<?php
/**
 * Objectiveweb core
 *
 * Global functions for interacting with the Objectiveweb class
 *
 */

// Global apps list
$_apps = array();

/**
 * ow()
 *
 * Singleton, instantiates objectiveweb with global defines
 *
 * @return Objectiveweb
 */
function ow() {
    static $ow;

    if (!$ow) {
        $ow = new Objectiveweb(array(
            "dsn" => sprintf("%s:host=%s;dbname=%s;charset=%s",
                OW_DB_DRIVER,
                OW_DB_HOST,
                OW_DB,
                OW_CHARSET),
            "username" => OW_DB_USER,
            "password" => OW_DB_PASS
        ));
    }

    return $ow;
}

function fetch($domain, $params = array()) {

    return ow()->fetch($domain, $params);
}

function get($domain_id, $id = null, $params = array())
{
    return ow()->get($domain_id, $id, $params);
}

function post($domain_id, $data) {
    return ow()->post($domain_id, $data);
}

function put($domain_id, $id, $data) {
    return ow()->put($domain_id, $id, $data);
}

function delete($domain_id, $id) {
    return ow()->delete($domain_id, $id);
}

/**
 * @param $id - The application ID
 * @param string $root - ROOT directory when looking for apps (defaults to web root)
 * @throws Exception
 */
function register_app($id, $root = ROOT) {
    global $_apps;

    $_init = "$root/$id/_init.php";
    if (!isset($_apps[$id])) {
        if (is_readable($_init)) {
            $_apps[$id] = $_init;
        } else {
            throw new Exception(sprintf(_('Impossible to register %s: %s not found'), $id, $_init));
        }
    }
}

function register_domain($id, Array $params) {
    ow()->register($id, $params);
}


function debug($str) {
    if (DEBUG) {
        error_log( (func_num_args() > 1) ? call_user_func_array('sprintf', func_get_args()) : func_get_arg(0));
    }
}