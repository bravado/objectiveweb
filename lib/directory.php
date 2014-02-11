<?php
/**
 * Objectiveweb Directory
 * Provides the /directory domain, for storing arbitrary user data and handlers for authentication
 *
 * User: guigouz
 * Date: 03/01/12
 * Time: 15:29
 */

// Default directory table
defined('OW_DIRECTORY') or define('OW_DIRECTORY', 'ow_directory');

defined('OW_SESSION_KEY') or define('OW_SESSION_KEY', 'OW_AUTH');

// uLogin settings
if (!defined('OW_SITE_KEY') || strlen(OW_SITE_KEY) < 40) {
    throw new Exception('Please define OW_SITE_KEY on your config file with at least 40 characters', 500);
}

// This is the one and only public include file for uLogin.
// Include it once on every authentication and for every protected page.
require_once('ulogin/config/all.inc.php');
require_once('ulogin/main.inc.php');

// Start a secure session if none is running
if (!sses_running())
    sses_start();

// We depend on phpgacl for permission management
require(dirname(__FILE__) . '/phpgacl/gacl.class.php');

function accounts_mapper($data)
{
    $data['profile'] = json_decode($data['profile']);

    return $data;
}

// domains starting with _ are not exposed through the REST interface
register_domain('_accounts', array(
    'table' => 'ow_accounts',
    'handler' => 'TableStore',
    'mapper' => 'accounts_mapper',
    'with' => array()
));

// Register the "directory" domain
register_domain('directory', array(
    'table' => "ow_aro",
    'handler' => 'TableStore',
    'hasMany' => array(
        'accounts' => array(
            'table' => 'ow_accounts',
            'key' => 'aro_id',
            'mapper' => 'accounts_mapper'
        ),
        'meta' => array(
            'table' => 'ow_directory',
            'key' => 'aro_id'
        )
    ),
    'mapper' => 'directory_mapper',
    //'get' => 'directory_get',
    'put' => 'directory_put',
    'post' => 'directory_post',
    'with' => array()
));

function directory_mapper($data)
{
    return array(
        'id' => $data['id'],
        'username' => $data['value'],
        'email' => $data['name'],
        'date_created' => $data['date_created'],
        'last_login' => $data['last_login']
    );
}

function directory_get($self, $id)
{
    if (is_numeric($id) || is_array($id)) {
        return $self->get($id);
    } else {
        $entries = $self->fetch("oid=$id");
        if (count($entries)) {
            $result = array('oid' => $id);
            foreach ($entries as $entry) {
                if (empty($entry['namespace'])) {
                    foreach ($entry as $k => $v) {
                        $result[$k] = $v;
                    }
                } else {
                    $result[$entry['namespace']] = $entry;
                }
            }
        } else {
            $result = null;
        }

        return $result;
    }
}

function directory_password_filter($data)
{
    if (empty($data['namespace']) && !empty($data['password'])) {
        $data['userPassword'] = md5($data['password']);
        unset($data['password']);
    }

    return $data;
}

function directory_post($handler, $data)
{

    if ($data['password'] != $data['confirm']) {
        throw new Exception('Passwords don\'t match');
    }


    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email');
    }

    // Create password hash with a new salt
    $hashed_password = ulPassword::Hash($data['password'], UL_PWD_FUNC);

    $now = ulUtils::nowstring();
    $past = date_format(date_create('1000 years ago'), UL_DATETIME_FORMAT);

    $aro = $handler->post(array(
        'name' => $data['email'],
        'value' => $data['username'],
        'password' => $hashed_password,
        'date_created' => $now,
        'last_login' => $now,
        'block_expires' => $past
    ));

    $accounts = ow_user('accounts');

    if ($accounts) {
        foreach ($accounts as $account) {
            put('_accounts', $account['id'], array('aro_id' => $aro['id']));
        }
    }

    return get('directory', $aro['id']);

}

function directory_put($self, $id, $data)
{
    if (!is_numeric($id)) {
        throw new Exception("Invalid ID for put (must be numeric)", 405);
    }

    return $self->put($id, directory_password_filter($data));
}


/**
 * @param $uid
 * @param $username
 * @param $ulogin uLogin
 * @throws Exception
 */
function auth_login($uid, $username, $ulogin)
{
    if (isset($_SESSION['appRememberMeRequested']) && ($_SESSION['appRememberMeRequested'] === true)) {
        // Enable remember-me
        if (!$ulogin->SetAutologin($username, true))
            throw new Exception("Cannot enable autologin", 500);

        unset($_SESSION['appRememberMeRequested']);
    } else {
        // Disable remember-me
        if (!$ulogin->SetAutologin($username, false))
            throw new Exception("Cannot disable autologin", 500);
    }

    // uid will always exist on the directory
    $_SESSION[OW_SESSION_KEY] = get('directory', $uid);

//    function ow_set_current_user($user) {
//        if (is_array($user)) {
//            $_SESSION[OW_SESSION_KEY] = $user;
//        }
//        else {
//            $user = get('directory', $user);
//            if (!$user) {
//                throw new Exception('Invalid user');
//            }
//            else {
//                $_SESSION['current_user'] = $user;
//            }
//        }
//    }


}

function auth_fail($uid, $username, $ulogin)
{
    throw new Exception(sprintf('Authentication Failure for %s', $username), 401);
}

function ow_logged_in()
{
    return isset($_SESSION[OW_SESSION_KEY]['id']);
}


function ow_logout()
{
    unset($_SESSION[OW_SESSION_KEY]);
}


function ow_login($username, $password, $remember = false)
{

    $ulogin = new uLogin('auth_login', 'auth_fail');

    // remember-me
    if ($remember) {
        $_SESSION['appRememberMeRequested'] = true;
    } else {
        unset($_SESSION['appRememberMeRequested']);
    }

    return $ulogin->Authenticate($username, $password);

}


class Acl extends OWFilter
{

    var $id = "acl";

    function post($data)
    {

        $current_user = ow_user();

        if ($current_user) {
            if (isset($this->handler->table->fields['_owner']) && empty($data['_owner'])) {
                $data['_owner'] = $current_user['oid'];
            }
        } else {
            if (!@$this->handler->params['public']) {
                throw new Exception('Not logged in', 401);
            }
        }

        return $data;
    }

    function put($id, $data)
    {
        $current_user = current_user();

        if ($current_user) {

        } else {
            if (!@$this->handler->params['public']) {
                throw new Exception('Not logged in', 401);
            }
        }

        return $data;
    }

}


/**
 * Returns the currently logged in user or NULL
 *
 * @param null $field
 * @return mixed
 * @throws Exception
 */
function ow_user($field = null)
{
    $_current_user = @$_SESSION[OW_SESSION_KEY];

    if (is_array($_current_user) && $field) {
        $field = explode(".", $field);

        for ($i = 0; $i < count($field); $i++) {
            if (isset($_current_user[$field[$i]])) {
                $_current_user = $_current_user[$field[$i]];
            } else {
                return NULL;
            }
        }
    }

    return $_current_user;

}


