<?php
/**
 * Handles Authentication
 *
 * User: guigouz
 * Date: 22/12/11
 * Time: 00:34
 */

register_domain('auth', array('handler' => 'AuthenticationHandler'));


class AuthenticationHandler extends OWHandler
{

    var $hybridauth;
    var $config;

    function init()
    {

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

    function fetch($params)
    {
        return $this->get(null);
    }

    function get($provider)
    {
        switch ($provider) {
            case null:
                // TODO DUMP LOCAL LOGIN INFO + all hybridauth logged in accounts
                return $_SESSION['current_user'];
                break;
            case 'logout':
                session_destroy();
                $this->hybridauth->logoutAllProviders();
                break;
            default:
                if ($this->hybridauth->isConnectedWith($provider)) {
                    $adapter = $this->hybridauth->getAdapter($provider);
                    $user_profile = (Array)$adapter->getUserProfile();
                    $schema = 'HA::'.$provider;
                    $local_profile = get('directory', array(
                            'schema' => $schema,
                            'identifier' => $user_profile['identifier'])
                    );

                    if (!$local_profile) {
                        $user_profile['schema'] = $schema;

                        // TODO não pode criar aqui, tem que ver se existe current user[id] e criar no mesmo {id, _schema}
                        $r = post('directory', $user_profile);

                        if ($r) {
                            $user_profile['id'] = $r['id'];
                        }
                    }

                    return $user_profile;
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

    function post($data)
    {

        $account = get('directory', array(
            'schema' => 'Account',
            'identifier' => $data['identifier']));

        if (!$account) {
            throw new Exception('User not found');
        }
        else {
            $userPassword = null;
            switch (strlen($account['userPassword'])) {
                case 32:
                    $userPassword = md5($data['password']);
                    break;
                default:
            }

            if($account['userPassword'] == $userPassword) {
                set_current_user(get('directory', $account['oid']));
            }
            else {
                throw new Exception('Invalid password supplied', 403);
            }
        }
    }
}