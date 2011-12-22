<?php


include "_init.php";


$auth = get('auth');


if($auth->get('Facebook')) {

    $referer = isset($_GET['redir']) ? $_GET['redir'] : $_SERVER['HTTP_REFERER'];

    header("Location: $referer");
    exit;
}
else {
    $auth->authenticate('Facebook');
}