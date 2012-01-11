<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 24/04/11
 * Time: 22:24
 */

/**
 * Route a particular request to a callback
 *
 *
 * @throws Exception
 * @param $request - HTTP Request Method + Request-URI Regex e.g. "GET /something/([0-9]+)/?"
 * @param $callback - A valid callback. Regex capture groups are passed as arguments to this function
 * @return void or data - If the callback returns something, it's responded accordingly, otherwise, nothing happens
 */

function route($request, $callback) {
    if(!is_callable($callback)) {
        throw new Exception(sprintf(_('%s: Invalid callback'), $callback), 500);
    }

    // TODO check if using PATH_INFO is ok in all cases (rewrite, different servers, etc)

    if(preg_match(sprintf("/^%s$/", str_replace('/', '\/', $request) ), "{$_SERVER['REQUEST_METHOD']} {$_SERVER['PATH_INFO']}", $params)) {
        array_shift($params);

        if(func_num_args() > 2) {
            $params = array_merge(array_slice(func_get_args(), 2), $params);
            //print_r($params); exit();
        }

        //print_r($params); exit;
        try {
            $response = call_user_func_array($callback, $params);
            if($response !== NULL) {
                respond($response);
            }
        }
        catch(Exception $ex) {
            respond($ex->getMessage(), $ex->getCode());
        }
    }
}

/**
 * Ensures that only the specified Content-type is accepted
 * @param $type The valid mime type
 * @return void
 */
function respond_to($type) {
    // TODO Only accept the specified Content-type especificado
    throw new Exception('Not implemented yet', 500);
}

function parse_post_body($decoded = true) {

    if($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {

        if(!empty($_POST)) {
            return $_POST;
        }
        else {
            $post_body = file_get_contents('php://input');

            if($decoded && ($post_body[0] == '{' || $post_body[0] == '[')) {
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

function respond($content, $code = 200) {

    header("HTTP/1.1 $code");

    if(is_array($content)) {

        $content = json_encode($content);
    }

    if($content[0] == '{' || $content[0] == '[') {
        header('Content-type: application/json');
    }

    exit($content);
}