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
            "debug" => defined('DEBUG')
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

    function authenticate($provider) {
        $this->hybridauth->authenticate($provider);
    }

    function get($provider)
    {
        if($provider == 'logout') {
            $this->hybridauth->logoutAllProviders();
            return null;
        }

        if ($this->hybridauth->isConnectedWith($provider)) {
            $adapter = $this->hybridauth->getAdapter($provider);
            $user_profile = $adapter->getUserProfile();

            return (Array) $user_profile;
        }
        else {
            if(isset($_GET['authenticate'])) {
                $this->authenticate($provider);
            }

            return null;
        }

    }
}