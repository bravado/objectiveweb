<?php
/**
 *
 *
 * User: guigouz
 * Date: 24/04/11
 * Time: 22:24
 */


function parse_post_body($decoded = true) {

    switch($_SERVER['REQUEST_METHOD']) {

        case 'POST':
            if (!empty($_POST)) {
                return $_POST;
            };
        case 'PUT':
            $post_body = file_get_contents('php://input');
            if($decoded) {
                if($post_body[0] == '{' || $post_body[0] == '[') {
                    return json_decode($post_body, true);
                }
                else {
                    parse_str($post_body, $return);
                    return $return;
                }
            }
            else {
                return $post_body;
            }
    }
}

function redirect($to) {
    header('Location: ' . url($to, true));
    exit();
}

function respond($content, $code = 200) {

    header("HTTP/1.1 $code");

    if (is_array($content)) {

        $content = json_encode($content);
    }

    if ($content[0] == '{' || $content[0] == '[') {
        header('Content-type: application/json');
    }

    exit($content);
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
    if (!is_callable($callback)) {
        throw new Exception(sprintf(_('%s: Invalid callback'), $callback), 500);
    }

    // TODO check if using PATH_INFO is ok in all cases (rewrite, different servers, etc)

    if (preg_match(sprintf("/^%s$/", str_replace('/', '\/', $request)), "{$_SERVER['REQUEST_METHOD']} {$_SERVER['PATH_INFO']}", $params)) {
        array_shift($params);

        if (func_num_args() > 2) {
            $params = array_merge($params, array_slice(func_get_args(), 2));
        }

        try {
            $response = call_user_func_array($callback, $params);
            if ($response !== NULL) {
                respond($response);
            }
        }
        catch (Exception $ex) {
            respond($ex->getMessage(), $ex->getCode());
        }
    }
}

/**
 * Constructs an URL for a given path
 *  - If the given url is external or exists as a file on disk, return that file's url
 *  - If the file does not exist, construct a url based on the current script + path info
 *  - If portions of the path exist, treat the rest as parameters (point to another controller)
 *
 * If the given path is NULL, returns the current url with protocol, port and so on
 *
 * Examples
 *  url('css/style.css'); returns '/some_root/my_application/css/style.css'
 *  url('1'); returns '/some_root/my_application/controller.php/1' (if we ran that command from controller.php)
 *  url('othercontroller.php/1/2'); returns '/some_root/my_application/othercontroller.php/1/2' (if othercontroller.php exists)
 *
 * @param $str
 * @param bool $return if false (default) prints the url, if true returns the url as string
 * @return string
 */
function url($str = null, $return = false) {
    if ($str == 'self' || empty($str)) {
        if (
            isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $protocol = 'https://';
        }
        else {
            $protocol = 'http://';
        }

        $url = $protocol . $_SERVER['HTTP_HOST'];

        // use port if non default
        $url .=
            isset($_SERVER['SERVER_PORT'])
                && (($protocol === 'http://' && $_SERVER['SERVER_PORT'] != 80) || ($protocol === 'https://' && $_SERVER['SERVER_PORT'] != 443))
                ? ':' . $_SERVER['SERVER_PORT']
                : '';

        $url .= empty($_SERVER['REQUEST_URI']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];

        // return current url
        $out = $url;
    }
    else {
        if (file_exists($str)) {
            $out = dirname($_SERVER['SCRIPT_NAME']) . '/' . $str;
        }
        else {
//            if(dirname($str) != '/' && file_exists(dirname($str))) {
//                $out = dirname($_SERVER['SCRIPT_NAME']) .'/'. dirname($str) . '/'.basename($str);
//            }
//            else {
            $out = $_SERVER['SCRIPT_NAME'] . '/' . $str;
//            }
        }
        // TODO check for pointers to other controllers + path info (other_controller.php/1/2 does not exist but other_controller.php could exist)

    }


    if ($return) {
        return $out;
    }
    else {
        echo $out;
    }
}
