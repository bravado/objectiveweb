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

    var $config;

    function init()
    {
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

    }

    function get($provider)
    {
        require_once(OW_LIB . '/hybridauth/Hybrid/Auth.php');

        $hybridauth = new Hybrid_Auth($this->config);

        if($provider == 'logout') {
            $hybridauth->logoutAllProviders();
            return null;
        }

        if ($hybridauth->isConnectedWith($provider)) {
            $adapter = $hybridauth->getAdapter($provider);
            $user_profile = $adapter->getUserProfile();

            return (Array) $user_profile;
        }
        else {

            if($_GET['authenticate'] == 'true') {
                $hybridauth->authenticate($provider);
            }

            return null;
        }

    }
}