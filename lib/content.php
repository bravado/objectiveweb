<?php
/**
 * ObjectiveWeb
 *
 * Content Management Library
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:09
 */

defined('OW_CONTENT') or define('OW_CONTENT', 'ow_content');

register_domain('content',
                array(
                     'schema' => array(
                         OW_CONTENT => array('title', 'key', 'content', 'lang')),
                     'handler' => 'ContentHandler'
                ));

class ContentHandler
{
    function post($params)
    {
        return put($params);
    }

    function get($params)
    {
        return get($params['key'], $params['namespace']);
    }

    function fetch($params)
    {
        return find($params);
    }
}


function put($object)
{

    if (empty($object['oid'])) {
        $object['oid'] = ow_create(OW_CONTENT, $object);
    }
    else {
        ow_update(OW_CONTENT, $object['oid'], $object);
    }
}

function get($key, $namespace = null)
{


    $object = ow_fetch(OW_CONTENT, array('`key`' => $key, 'namespace' => $namespace));

    if ($object) {
        return $object[0];
    }
    else {
        return null;
    }
}

function find($params)
{
    return ow_fetch(OW_CONTENT, $params);
}