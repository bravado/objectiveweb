<?php
/**
 * Objectiveweb Authentication Controller
 *
 * GET /
 * GET /logout      Logs out
 * POST /
 *
 *
 * User: guigouz
 * Date: 8/14/13
 * Time: 12:09 AM
 * To change this template use File | Settings | File Templates.
 */

include "_init.php";

require_once(OW_LIB . '/hybridauth/Hybrid/Auth.php');

/**
 * GET /
 *  Returns the current authenticated user or 401 if not authenticated
 *
 * GET /logout
 *  Logs out
 *
 * GET /{Provider}
 *  Authenticates with oAuth {Provider}
 */
route("GET /(\\S*)?", "auth_get");

/**
 * POST /
 *  Authenticates a user { username, password, remember = 0/1 }

 */
route("POST /?", "authenticate_user");

function auth_get($provider = null)
{

    if (!$provider) {
        if (ow_user()) {
            return ow_user();
        } else {
            // TODO if not AJAX, show login page
            throw new Exception('Not logged in', 401);
        }
    }

    // As $provider was specified, configure HybridAuth
    $config = array(
        "base_url" => OW_URL . '/lib/hybridauth/',
        "providers" => array(),
        "debug" => DEBUG
    );

    // Process Facebook Credentials
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
            ow_logout();
            $hybridauth->logoutAllProviders();
            break;
        default:
            if ($hybridauth->isConnectedWith($provider)) {

                if(empty($_SESSION[OW_SESSION_KEY])) {
                    $_SESSION[OW_SESSION_KEY] = array();
                }

                $adapter = $hybridauth->getAdapter($provider);
                $user_profile = (Array)$adapter->getUserProfile();

                $local_profile = get('_accounts',array(
                    'provider' => $provider,
                    'identifier' => $user_profile['identifier']));

                if(!$local_profile) {

                        $account = array(
                            'aro_id' => ow_user('id'),
                            'provider' => $provider,
                            'identifier' => $user_profile['identifier'],
                            'profile' => $user_profile
                        );
                        $account_id = post('_accounts', $account);

                        $account['id'] = $account_id['id'];
                        $_SERVER[OW_SESSION_KEY][$provider] = $account;
                }

//              if(isset($_SESSION['current_user'])) copiar o displayName, email, etc
                return $local_profile;

            } else {
//                if (isset($_GET['authenticate'])) {
                    $hybridauth->authenticate($provider);
//                }

                return null;
            }
    }
}

function authenticate_user()
{
    $data = parse_post_body();

    ow_login($data['username'], $data['password'], @$data['remember'] == 1);


}