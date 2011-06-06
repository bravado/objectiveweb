<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 06/06/11
 * Time: 14:30
 */
 
class OW_Driver {

    var $id;
    var $params;

    function OW_Driver($id, $params) {
        $this->id = $id;
        $this->params = $params;
        $this->init();
    }


    function init() {

    }
    
    /**
     * Manages metadata at $oid/$meta_key
     * @param  $meta_key
     * @param  $meta_value
     * @return void
     */
    function meta($oid, $meta_key, $meta_value = null)
    {

    }


    function acl($oid, $rules = null)
    {

    }

    /**
     * Retrieves an object from a domain given its OID
     *
     * @param  $domain
     * @param  $oid
     * @param array $params
     * @return void
     */
    function get($oid)
    {
    }


    function fetch($cond = array(), $acl = array())
    {

    }


    /**
     * Creates a new Object on a domain
     *
     * @param null $data The object data
     * @param null $owner The object owner
     * @param null $metadata The object metadata

     * @return string|The object's new oid.
     */
    function create($data = null, $owner = null, $metadata = array())
    {

    }

    /**
     * Writes data to an existing object
     * @param  $oid
     * @param Array $data
     * @param Array $metadata
     * @return array The changed data
     */
    function write($oid, $data, $metadata = array())
    {

    }

    function delete($oid)
    {

    }
}