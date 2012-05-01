<?php
/**
 *
 *
 * User: guigouz
 * Date: 06/06/11
 * Time: 12:49
 */

function curl_get($url, $params = null, $decode_response = false) {

    if ($params) {
        if (is_array($params)) {
            $params = http_build_query($params);
        }

        if (strpos($url, '?')) {
            $url .= '&' . $params;
        }
        else {
            $url .= '?' . $params;
        }
    }

    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($c, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($c);
    $err = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    if ($err / 100 != 2) {
        // error
        throw new Exception($data, $err);
    }
    else {
        if ($decode_response) {
            return json_decode($data, true);
        }
        else {
            return $data;
        }
    }
}


function curl_post($url, $params = null, $decode_response = false) {
    if ($params) {
        if (is_array($params)) {
            $params = http_build_query($params);
        }
    }
}

function mkdirs($dir, $mode = 0777) {
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);

}

// Internationalization fallback (no internationalization)
if (!function_exists('_')) {
    function _($string) {
        return $string;
    }
}

function today() {
    return date('Y-m-d');
}

function now() {
    return date('Y-m-d H:i:s');
}

function debug($str) {
    if (DEBUG) {
        error_log(call_user_func_array('sprintf', func_get_args()));
    }
}

/**
 *
 * From http://stackoverflow.com/questions/4260086/php-how-to-use-array-filter-to-filter-array-keys
 *
 * @param $array
 * @param array $valid_keys
 * @return array
 */
function array_cleanup($array, $valid_keys = array()) {
    if(!is_array($valid_keys)) {
        $valid_keys = array($valid_keys);
    }

    return array_intersect_key($array, array_flip($valid_keys));
}
