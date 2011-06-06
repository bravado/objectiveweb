<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 06/06/11
 * Time: 12:49
 */
 
function curl_get($url, $params = null, $decode_response = false) {

    if($params) {
        if(is_array($params)) {
            $params = http_build_query($params);
        }

        if(strpos($url, '?')) {
            $url .= '&'.$params;
        }
        else {
            $url .= '?'.$params;
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

    if($err / 100 != 2) {
        // error
        throw new Exception($data, $err);
    }
    else {
        if($decode_response) {
            return json_decode($data, true);
        }
        else {
            return $data;
        }
    }
}


function curl_post($url, $params) {
    if($params) {
        if(is_array($params)) {
            $params = http_build_query($params);
        }
    }
}