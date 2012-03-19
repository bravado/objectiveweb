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

require_once(dirname(__FILE__) . '/functions.shortcodes.php');

add_shortcode('date', 'tpl_date');
add_shortcode('each', 'tpl_each');
add_shortcode('fetch', 'tpl_fetch');
add_shortcode('get', 'tpl_get');
add_shortcode('group', 'tpl_group');
add_shortcode('if', 'tpl_if');
add_shortcode('url', 'tpl_url');
add_shortcode('val', 'tpl_value');

function tpl_date($atts, $content = null, $code = "", $context = null) {
    $date = $context[$content];

    $format = empty($atts['format']) ? 'd/m/Y h:m:i' : $atts['format'];

    return date($format, strtotime($date));
}

function tpl_each($atts, $content = null, $code = "", $context = null)
{
    $items = isset($atts['in']) ? $context[$atts['in']] : $context;

    if(isset($atts['group'])) {
        tpl_set('group', $atts['group']);
        unset($atts['group']);
    }

    $out = '';

    for($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        $item['_index'] = $i;
        $out .= do_shortcode($content, $item);
    }

    return $out;
}

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

    $domain = $atts['from'];
    unset($atts['from']);

    $results = fetch($domain, $atts);

    return tpl_each($atts, $content, $code="", $results);

}

$_groupcurrent = null;
$_groupindex = null;
function tpl_group($atts, $content, $code="", $context) {
    global $_groupcurrent, $_groupindex;

    if($_groupcurrent != $context[$atts['by']]) {
        $_groupcurrent = $context[$atts['by']];
        $_groupindex = 0;
    }
    else {
        $_groupindex++;
    }

    $context['_group'] = $_groupcurrent;
    $context['_groupindex'] = $_groupindex;

    return do_shortcode($content, $context);
}

function tpl_get($atts, $content = null, $code = "", $context = null)
{
    $rsrc = get($atts['from'], $atts['id']);

    return $rsrc ? do_shortcode($content, $rsrc) : '';
}

function tpl_if($atts, $content = null, $code = "", $context = null) {

    foreach($atts as $k => $v) {

        if(!isset($context[$k])) {
            debug("tpl_if: variable %s undefined", $k);
            return '';
        }

        // Helpers for numeric types
        if(is_numeric($context[$k])) {
            switch($v) {
                case 'even':
                    if($context[$k] % 2 == 0) {
                        return $content;
                    }
                    break;
                case 'odd':
                    if($context[$k] % 2 != 0) {
                        return $content;
                    }
                    break;
            }
        }

        if($context[$k] == $v) {
            return do_shortcode($content, $context);
        }
        else {
            return '';
        }
    }
}

function tpl_url($atts, $content = null, $code = "", $context = null) {
    return url(do_shortcode($content, $context), true);
}

function tpl_value($atts, $content = null, $code = "", $context = null)
{
    return isset($context[$content]) ? $context[$content] : @$atts['default'];
}

function render($template, $context = null, $return = false)
{

    if (is_array($template)) {
        $template = get($template[0], $template[1]);
    }
    else {
        if (is_readable($template)) {
            $template = file_get_contents($template);
        }
        else {
            throw new Exception('Invalid template ' . $template);
        }
    }

    if ($return) {
        return do_shortcode($template, $context);
    }
    else {
        echo do_shortcode($template, $context);
        return true;
    }
}