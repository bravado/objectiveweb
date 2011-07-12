<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 24/04/11
 * Time: 22:24
 */

define('OW_URLPATTERN', '/\/(?P<domain>\w+)\/?(?P<id>\w+)?\/?(?P<attachment>.*)?/');

function respond_to($type) {
    // TODO só aceitar o Content-type especificado
}

function parse_post_body($decoded = true) {

    if($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {

        if(!empty($_POST)) {
            return $_POST;
        }
        else {
            $post_body = file_get_contents('php://input');

            if($post_body[0] == '{' || $post_body[0] == '[') {
                return json_decode($post_body, true);
            }
            else {
                return $post_body;
            }
        }
    }
    else {
        return null;
    }

}

function parse_session_acl() {

    // TODO return the current session's ACL

    return array();
}

function respond($content, $code = 200) {

    header("HTTP/1.1 $code");

    if(is_array($content)) {
        header('Content-type: application/json');
        $content = json_encode($content);
    }


    exit($content);
}