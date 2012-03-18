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

                break;
            case 'logout':
                // TODO também matar a session local
                $this->hybridauth->logoutAllProviders();
                break;
            default:
                if ($this->hybridauth->isConnectedWith($provider)) {
                    $adapter = $this->hybridauth->getAdapter($provider);
                    $user_profile = (Array)$adapter->getUserProfile();


                    $local_profile = get('directory', $user_profile['identifier']);

                    if (!$local_profile) {
                        $user_profile['oid'] = $user_profile['identifier'];
                        $user_profile['uid'] = $user_profile['identifier'];
                        create('directory', $user_profile);
                    }

                    return $user_profile;
                }
                else {
                    if (isset($_GET['authenticate'])) {
                        $this->hybridauth->authenticate($provider);
                    }

                    return null;
                }
        }
    }

    function post($data)
    {
        throw new Exception("Local login not implemented");
    }
}