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

add_shortcode('current_user', 'tpl_current_user');
add_shortcode('date', 'tpl_date');
add_shortcode('each', 'tpl_each');
add_shortcode('error', 'tpl_error');
add_shortcode('errors', 'tpl_errors');
add_shortcode('fetch', 'tpl_fetch');
add_shortcode('get', 'tpl_get');
add_shortcode('group', 'tpl_group');
add_shortcode('if', 'tpl_if');
add_shortcode('if2', 'tpl_if');
add_shortcode('if3', 'tpl_if');
add_shortcode('url', 'tpl_url');
add_shortcode('val', 'tpl_val');

function tpl_current_user($atts, $content) {

    return val($content, current_user());
}

function tpl_date($atts, $content = null, $code = "", $context = null)
{
    if(isset($context[$content])) {
        $date = $context[$content];
    }
    else {
        $date = $content;
    }

    $format = empty($atts['format']) ? 'd/m/Y h:m:i' : $atts['format'];

    return date($format, strtotime($date));
}

function tpl_each($atts, $content = null, $code = "", $context = null)
{
    $items = isset($atts['in']) ? val($atts['in'], $context) : $context;

    $out = '';

    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        $item['_index'] = $i;
        $out .= do_shortcode($content, $item);
    }

    return $out;
}

function tpl_error($atts, $content = null, $code = "", $context = null) {
    return error($content);
}

function tpl_errors($atts, $content = null, $code = "", $context = null) {
    $out = '';
    if(have_errors()) {
        foreach(errors() as $error) {
            $out .= do_shortcode($content, $error);
        }
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

    return tpl_each($atts, $content, $code, $results);

}

$_groupcurrent = null;
$_groupindex = null;
function tpl_group($atts, $content, $code = "", $context)
{
    global $_groupcurrent, $_groupindex;

    if ($_groupcurrent != $context[$atts['by']]) {
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

function tpl_if($atts, $content = null, $code = "", $context = null)
{
    $test = FALSE;
    if (isset($atts[0])) {
        if($atts[0][0] == '!') {
            $_not = true;
            $atts[0] = substr($atts[0], 1);
        }
        else {
            $_not = false;
        }

        $test = val($atts[0], $context) != null;

        if($_not) {
            $test = !$test;
        }
    }
    else {

        // TODO não deve estar funcionando para mais variáveis
        // Definir operador para mais variáveis AND|OR
        foreach ($atts as $k => $v) {

            $val = val($k, $context);

            if ($val === NULL) {
                debug("tpl_if: variable %s undefined", $k);
                return '';
            }

            $op = 0;
            while (in_array($v[0], array("!", ">", "<"))) {
                switch ($v[0]) {
                    case '!':
                        $op |= 1;
                        break;
                    case '>':
                        $op |= 2;
                        break;
                    case '<':
                        $op |= 4;
                }

                $v = substr($v, 1);
            }

            // Helpers for numeric types
            if (is_numeric($val)) {
                switch ($v) {
                    case 'even':
                        $test = ($val % 2 == 0);
                        break;
                    case 'odd':
                        $test = ($val % 2 != 0);
                        break;
                    default:
                        switch ($op) {
                            case 0:
                                $test = ($val == $v);
                                break;
                            case 2:
                                $test = ($val > $v);
                                break;
                            case 4:
                                $test = ($val < $v);
                                break;
                        }


                }
            }
            else {
                $test = ($val == $v);
            }

            if ($op & 1) {
                $test = !$test;
            }

        }
    }

    if ($test) {
        return do_shortcode($content, $context);
    }
    else {
        return '';
    }
}

function tpl_url($atts, $content = null, $code = "", $context = null)
{
    return url(do_shortcode($content, $context), true);
}

function tpl_val($atts, $content = null, $code = "", $context = null)
{
    if(empty($content)) {
        $return = $context;
    }
    else {
        $return = val($content, $context);
    }

    if(!$return) {
        if(isset($atts['default'])) {
            if($atts['default'][0] == '$') {
                $return = val(substr($atts['default'], 1), $context);
            }
            else {
                $return = $atts['default'];
            }
        }
    }

    switch (@$atts['format']) {
        case 'json':
            return json_encode($return);
        case 'php':
            return serialize($return);
        case 'dump':
            return print_r($return, true);
        default:
            return $return;
    }
}

/**
 * Renders a template from file or resource
 * @param $template
 * @param null $context
 * @param bool $return
 * @throws Exception
 */

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

    render_str($template, $context, $return);
}

/**
 * Renders a template from string
 * @param $str
 * @param $context
 * @param bool $return
 * @return bool|string
 */
function render_str($str, $context, $return = false) {

    if ($return) {
        return do_shortcode($str, $context);
    }
    else {
        echo do_shortcode($str, $context);
        return true;
    }
}

/**
 * Extracts a value from context or global variables
 * @param $content
 * @param $context
 * @return array|bool|mixed|null
 */
function val($content, $context) {
    $val = explode(".", $content);
    $return = null;
    switch($val[0]) {
        case '$':
            break;
        case 'current_user':
            $context = current_user();
            break;
        case 'GET':
            $context = $_GET;
            break;
        case 'POST':
            $context = $_POST;
            break;
        case 'SERVER':
            $context = $_SERVER;
            break;
        default:
            $context = @$context[$val[0]];
            break;
    }

    $return = $context;
    for($i = 1; $i < count($val); $i++) {
        if(isset($return[$val[$i]])) {
            $return = $return[$val[$i]];
        }
        else {
            debug("%s not found in %s", $content, json_encode($context));
            $return = null; // TODO retornar erro ?
        }
    }

    return $return;
}




//-----------------------------------------------------------------------------
// TRATAMENTO DE ERROS E VALIDAÇÃO
//-----------------------------------------------------------------------------

$ERROR = array();

function errors() {
    global $ERROR;
    return $ERROR;
}

/**
 * Marca um erro
 * @global <type> $ERROR
 * @param <type> $key - chave do erro (identificação)
 * @param <type> $value - String detalhando o erro
 */
function error($key, $value = null) {
    global $ERROR;

    if($value) {
        $ERROR[$key] = $value;
    } else {
        if(isset($ERROR[$key])) {
            return $ERROR[$key];
        }
        else {
            return FALSE;
        }
    }
}

function have_errors($field = null) {
    global $ERROR;

    if(!$field) {
        return count($ERROR) > 0;
    } else {
        return isset($ERROR[$field]);
    }
}

