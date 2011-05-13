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

function parse_post_body() {

    if($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        // TODO verificar se é JSON

        return json_decode(file_get_contents('php://input'), true);
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
        $content = json_encode($content);
    }
    
    exit($content);
}