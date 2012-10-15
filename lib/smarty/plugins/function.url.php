<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.url.php
 * Type:     function
 * Name:     url
 * Purpose:  constructs a relative url
 * -------------------------------------------------------------
 */
function smarty_function_url($params) {
    return url(@$params['to'], true);
}