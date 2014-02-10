<?php
/**
 * Created by IntelliJ IDEA.
 * User: guigouz
 * Date: 8/14/13
 * Time: 12:09 AM
 * To change this template use File | Settings | File Templates.
 */

include "_init.php";

require_once(OW_LIB . '/hybridauth/Hybrid/Auth.php');

route("GET /(\\S*)?", "auth_get");

route("POST /?", "authenticate_user");

function auth_get($provider = null)
{

    if (!$provider) {
        if (current_user('oid')) {
            return current_user();
        } else {
            throw new Exception('Not logged in', 401);
        }
    }

    // Perform auth using HybridAuth
    $config = array(
        "base_url" => OW_URL . '/lib/hybridauth/',
        "providers" => array(),
        "debug" => DEBUG
    );

    if (defined('AUTH_FACEBOOK_ID')) {
        $config['providers']['Facebook'] = array(
            "enabled" => true,
            "keys" => array("id" => AUTH_FACEBOOK_ID, "secret" => AUTH_FACEBOOK_SECRET),
            "scope" => ""
        );
    }

    $hybridauth = new Hybrid_Auth($config);

    switch ($provider) {
        case 'logout':
            session_destroy();
            $hybridauth->logoutAllProviders();
            break;
        default:
            if ($hybridauth->isConnectedWith($provider)) {
                $adapter = $hybridauth->getAdapter($provider);
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

            } else {
                if (isset($_GET['authenticate'])) {
                    $hybridauth->authenticate($provider);
                }

                return null;
            }
    }
}

function authenticate_user()
{
    $data = parse_post_body();

    ow_login($data['username'], $data['password'], @$data['remember'] == 1);


}