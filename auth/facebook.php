<?php
/**
 * Facebook authentication module
 * https://developers.facebook.com/docs/authentication/
 *
 * User: guigouz
 * Date: 19/05/11
 * Time: 19:07
 */

include "../_init.php";

// connects to the domain "apps"
$apps = get_domain('apps');

// fetches the "core" app
$core_app = $apps->get('core');

// facebook preferences are stored as metadata
$preferences = $domain->meta($core_app, 'facebook');

$app_id = $preferences['app_id'];
$app_secret = $preferences['app_secret'];
$my_url = ($_SERVER['SERVER_PORT'] == 80 ? "http" : "https") . "://{$_SERVER['SERVER_NAME']}/{$_SERVER['REQUEST_URI']}";

$code = $_REQUEST["code"];

if (empty($code)) {
    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
    $dialog_url = "https://www.facebook.com/dialog/oauth?client_id="
                  . $app_id . "&redirect_uri=" . urlencode($my_url) . "&state"
                  . $_SESSION['state'];

    echo("<script> top.location.href='" . $dialog_url . "'</script>");
}

if ($_REQUEST['state'] == $_SESSION['state']) {
    $token_url = "https://graph.facebook.com/oauth/access_token?"
                 . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
                 . "&client_secret=" . $app_secret . "&code=" . $code;

    $response = file_get_contents($token_url);
    $params = null;
    parse_str($response, $params);

     $graph_url = "https://graph.facebook.com/me?access_token="
                  . $params["access_token"];

     $user = json_decode(file_get_contents($graph_url));
     echo("Hello " . $user->name);
   }
else {
    echo("The state does not match. You may be a victim of CSRF.");
}

?>