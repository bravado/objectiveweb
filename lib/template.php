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

defined('TEMPLATES_ROOT') || define('TEMPLATES_ROOT', ROOT.'/templates');

/**
 * All templates are listed on the "templates" domain
 */
register_domain('templates', array(
    'handler' => 'FileStore',
    'root' => TEMPLATES_ROOT
));

add_shortcode('fetch', 'tpl_fetch');
add_shortcode('value', 'tpl_value');

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

function tpl_value($atts, $content = null, $code = "", $context = null)
{
    return isset($context[$content]) ? $context[$content] : @$atts['default'];
}


function render($template, $context = null) {

    $template = get('templates', $template);

    if(!$template) {
        throw new Exception('Invalid template!');
    }

    echo do_shortcode($template, $context);
}