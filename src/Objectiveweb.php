<?php

use Objectiveweb\DB;
use Objectiveweb\Handler;

class Objectiveweb {

    const VERSION = "0.10.0";

    private $domains = array();

    public $db;

    public function __construct($config) {
        $this->db = new DB($config['dsn'], $config['username'], $config['password']);
    }

    public function fetch($domain, $params = array()) {

        // Handle views (/domain/_view)
        preg_match('/(_?[a-z]+)\/?_?([a-z]*)\/?(.*)/', $domain, $m);

        $handler = $this->get($m[1]);

        if ($m[2]) {
            $view_handler = @$handler->params['views'][$m[2]];

            // if the view is an array, perform this query
            if (is_array($view_handler)) {
                $params = array_merge($params, $view_handler);
            } elseif (is_callable($view_handler)) {
                $params = call_user_func_array($view_handler, array($m[3], $params));
            } else {
                throw new Exception(_('View not found'), 404);
            }
        }

        if (isset($handler->params['fetch'])) {
            $result = call_user_func_array($handler->params['fetch'], array($handler, $params));
        } else {
            $params = $handler->apply_filters('fetch', null, $params);
            $result = $handler->fetch($params);
        }


        return $handler->apply_filters('get', null, $result);
    }

    public function get($domain_id, $id = null, $params = array()) {
        if (empty($this->domains[$domain_id])) {
            throw new Exception(_('Domain not found'), 404);
        }

        if (empty($this->domains[$domain_id]['instance'])) {

            $class = $this->domains[$domain_id]['handler'];

            if (!class_exists($class)) {
                throw new Exception(sprintf(_('Invalid Handler %s'), $class), 500);
            }

            /** @var $instance Handler */
            $instance = new $class($this, $domain_id, $this->domains[$domain_id]);

            $this->domains[$domain_id]['instance'] = $instance;
        }

        /** @var $handler Handler */
        $handler = $this->domains[$domain_id]['instance'];

        $params = $handler->apply_filters('fetch', $id, $params);

        if ($id) {

            if (isset($handler->params['get'])) {
                $rsrc = call_user_func_array($handler->params['get'], array($handler, $id, $params));
            } else {
                $rsrc = $handler->get($id, $params);
            }

            // Filter result
            if ($rsrc) {
                $rsrc = $handler->apply_filters('get', $id, $rsrc);
            }
            else {
                throw new Exception(sprintf(_('%s %s not found'), $domain_id, $id), 404);
            }

            return $rsrc;
        } else {
            return $handler;
        }


    }

    function post($domain, $data) {

        /** @var $handler \Objectiveweb\Handler */
        $handler = $this->get($domain);

        $data = $handler->apply_filters('post', null, $data);

        if (isset($handler->params['post'])) {
            return call_user_func_array($handler->params['post'], array($handler, $data));
        } else {
            return $handler->post($data);
        }
    }

    function put($domain, $id, $data) {

        $handler = get($domain);

        $data = $handler->apply_filters('put', $id, $data);

        if (isset($handler->params['put'])) {
            return call_user_func_array($handler->params['put'], array($handler, $id, $data));
        } else {
            return $handler->put($id, $data);
        }
    }

    function delete($domain, $id) {
        /** @var $handler \Objectiveweb\Handler */
        $handler = $this->get($domain);

        $handler->apply_filters('delete', $id);

        if (isset($handler->params['delete'])) {
            return call_user_func_array($handler->params['delete'], array($handler, $id));
        } else {
            return $handler->delete($id);
        }
    }

    /**
     * Register a domain
     *
     * @param String $domain_id The domain identifier
     * @param array $params Parameters
     *  [
     *    "handler" => Handler,
     *    "get" => callback,
     *    "post" => callback,
     *    "put" => callback,
     *    "delete" => callback,
     *    "fetch" => callback,
     *    "with" => [
     *         "service" => Service,
     *         ...
     *    ],
     *    "views" => [
     *         "name" => array() | callback
     *    ]
     *  ]
     * @throws Exception
     */
    public function register($domain_id, $params = array()) {

        if (isset($this->domains[$domain_id])) {
            throw new Exception(sprintf(_('Domain %s already registered'), $domain_id));
        }

        if (!is_array($params)) {
            $params = json_decode($params, true);

            if ($params === null) {
                throw new Exception(_('Invalid domain parameters'), 500);
            }
        }

        $this->domains[$domain_id] = $params;
    }



    public function version() {
        if (DEBUG) {
            return sprintf('{ "objectiveweb": "%s", "domains": %s }', Objectiveweb::VERSION, json_encode($this->domains));
        } else {
            return sprintf('{ "objectiveweb": "%s" }', Objectiveweb::VERSION);
        }
    }
}