<?php

namespace Objectiveweb;

use Exception;

abstract class Service {

    var $handler;

    function __construct($handler) {
        $this->handler = $handler;
    }

    /**
     * Called before deleting a resource
     * @param $id String the resource id
     */
    function delete($id) {

    }

    /**
     * Applyed on the query parameters before fetching
     * a resource or domain (id may be null)
     * @param $id
     */
    function fetch($id = null, $params = array()) {
        return $params;
    }

    /**
     * Called after fetching a resource
     * @param $id Resource id (may be null)
     * @param $data Resource content
     * @return mixed Modified resource content
     */
    function get($id, $data) {
        return $data;
    }

    /**
     * Called before creating a new resource
     * @param $data Array with the new resource contents
     * @return Array Modified data which will be persisted
     */
    function post($id, $data) {
        return $data;
    }


    /**
     * Called before modifying a resource
     * @param $id String The resource ID
     * @param $data Array with new contents
     * @return mixed Modified data which will be persisted
     */
    function put($id, $data) {
        return $data;
    }

    /**
     * Handle direct requests to this service (/domain/id/_service)
     * @param $domain
     * @param $id
     * @throws Exception
     */
    function service($id) {
        throw new Exception('Not implemented', 500);
    }
}