<?php
/**
 * ObjectiveWeb
 *
 * Template Engine
 * 
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:52
 */
 
require_once(dirname(__FILE__).'/functions.shortcodes.php');

//defined('TEMPLATES_ROOT') || define('TEMPLATES_ROOT', ROOT.'/templates');

/**
 * All templates are listed on the "templates" domain
 */
//

add_shortcode('fetch', 'tpl_fetch');
add_shortcode('val', 'tpl_value');
add_shortcode('get', 'tpl_get');

function tpl_fetch($atts, $content = null, $code = "", $context = null)
{
    // $atts    ::= array of attributes
    // $content ::= text within enclosing form of shortcode element
    // $code    ::= the shortcode found, when == callback name
    // $context ::= the current context
    // examples: [my-shortcode]
    //           [my-shortcode/]
    //           [my-shortcode foo='bar']
    //           [my-shortcode foo='bar'/]
    //           [my-shortcode]content[/my-shortcode]
    //           [my-shortcode foo='bar']content[/my-shortcode]

    parse_str($atts['q'], $q);
    $results = fetch($atts['from'], $q);

    $out = '';
    foreach($results as $result) {
        $out .= do_shortcode($content, $result);
    }

    return $out;
}

function tpl_get($atts, $content = null, $code = "", $context = null) {
    $rsrc = get($atts['from'], $atts['id']);

    return $rsrc ? do_shortcode($content, $rsrc) : '';
}

function tpl_value($atts, $content = null, $code = "", $context = null)
{
    return isset($context[$content]) ? $context[$content] : @$atts['default'];
}


function render($template, $context = null, $return = false) {

    if(is_array($template)) {
        $template = get($template[0], $template[1]);
    }
    else {
        if(is_readable($template)) {
            $template = file_get_contents($template);
        }
        else {
            throw new Exception('Invalid template!');
        }
    }

    if($return) {
        return do_shortcode($template, $context);
    }
    else {
        echo do_shortcode($template, $context);
        return true;
    }
}