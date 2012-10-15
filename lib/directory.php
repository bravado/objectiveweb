<?php
/**
 * Objectiveweb Directory
 * Provides the /directory domain, for storing arbitrary user/app data and handlers for authentication
 *
 * User: guigouz
 * Date: 03/01/12
 * Time: 15:29
 */

// Default directory table
defined('OW_DIRECTORY') or define('OW_DIRECTORY', 'ow_directory');

// Register the "directory" domain
register_domain('directory', array(
    'handler' => 'ObjectStore',
    'table' => OW_DIRECTORY,
    'get' => 'directory_get',
    'put' => 'directory_put'
));

// Register the "auth" domain
register_domain('auth', array(
    'handler' => 'AuthenticationHandler'));

function directory_get($self, $id) {
    if (is_numeric($id) || is_array($id)) {
        return $self->get($id);
    }
    else {
        $entries = $self->fetch("oid=$id");
        if (count($entries)) {
            $result = array('oid' => $id);
            foreach ($entries as $entry) {
                if (empty($entry['namespace'])) {
                    foreach ($entry as $k => $v) {
                        $result[$k] = $v;
                    }
                }
                else {
                    $result[$entry['namespace']] = $entry;
                }
            }
        }
        else {
            $result = null;
        }

        return $result;
    }
}

function directory_put($self, $id, $data) {
    if (!is_numeric($id)) {
        throw new Exception("Invalid ID for put (must be numeric)", 405);
    }

    return $self->put($id, $data);
}

function set_current_user($user) {
    if (is_array($user)) {
        $_SESSION['current_user'] = $user;
    }
    else {
        $user = get('directory', $user);
        if (!$user) {
            throw new Exception('Invalid user');
        }
        else {
            $_SESSION['current_user'] = $user;
        }
    }
}

class AuthenticationHandler extends OWHandler {

    var $hybridauth;
    var $config;

    function init() {

        require_once(OW_LIB . '/hybridauth/Hybrid/Auth.php');

        $this->config = array(
            "base_url" => OW_URL . '/lib/hybridauth/',
            "providers" => array(),
            "debug" => DEBUG
        );

        if (defined('AUTH_FACEBOOK_ID')) {
            $this->config['providers']['Facebook'] = array(
                "enabled" => true,
                "keys" => array("id" => AUTH_FACEBOOK_ID, "secret" => AUTH_FACEBOOK_SECRET),
                "scope" => ""
            );
        }

        $this->hybridauth = new Hybrid_Auth($this->config);
    }

    function fetch($params) {
        return $this->get(null);
    }

    function get($provider) {
        switch ($provider) {
            case null:

                if(current_user('oid')) {
                    return current_user();
                }
                else {
                    throw new Exception('Not authorized', 403);
                }

                break;
            case 'logout':
                session_destroy();
                $this->hybridauth->logoutAllProviders();
                break;
            default:
                if ($this->hybridauth->isConnectedWith($provider)) {
                    $adapter = $this->hybridauth->getAdapter($provider);
                    $user_profile = (Array)$adapter->getUserProfile();
                    $namespace = 'HA::' . $provider;
                    $local_profile = get('directory')->get(array(
                            'namespace' => $namespace,
                            'identifier' => $user_profile['identifier'])
                    );

                    if (!$local_profile) {
                        $user_profile['namespace'] = $namespace;
                        $user_profile['oid'] = current_user('oid');

                        // TODO post deve retornar OID também (objectstore SE tiver field oid)
                        $r = post('directory', $user_profile);

                        $local_profile = get('directory', $r['id']);
                    }

//                    if(isset($_SESSION['current_user'])) copiar o displayName, email, etc
                    return $local_profile;

                }
                else {
                    if (isset($_GET['authenticate'])) {
                        $this->authenticate($provider);
                    }

                    return null;
                }
        }
    }

    function authenticate($provider) {
        return $this->hybridauth->authenticate($provider);
    }

    function post($data) {

        $account = get('directory', array(
            'namespace' => '',
            'identifier' => $data['identifier']));

        if (!$account) {
            throw new Exception('User not found', 404);
        }
        else {
            $userPassword = null;
            switch (strlen($account['userPassword'])) {
                case 32:
                    $userPassword = md5($data['password']);
                    break;
                default:
            }

            if ($account['userPassword'] == $userPassword) {
                $user = get('directory', $account['oid']);
                set_current_user($user);
                return $user;
            }
            else {
                throw new Exception('Invalid password supplied', 403);
            }
        }
    }

}
