<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 24/04/11
 * Time: 22:24
 */


function respond_to($type) {
    // TODO só aceitar o Content-type especificado
}

function parse_post_body($decoded = true) {

    if($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {

        if(!empty($_POST)) {
            return $_POST;
        }
        else {
            $post_body = F3::get('REQBODY');

            if($post_body[0] == '{' || $post_body[0] == '[') {
                return json_decode($post_body, true);
            }
            else {
                parse_str($post_body, $output);
                return $output;
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