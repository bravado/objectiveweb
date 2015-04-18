<?php
namespace Objectiveweb\Store;

use Objectiveweb\DB;
use Exception;

class SQLStore extends \Objectiveweb\Handler {

    /**
     * @var DB
     */
    private $db;

    protected $defaults = array(
        'extends' => null,
        'hasOne' => array(),
        'hasMany' => array(),
        'belongsTo' => array(),
        'mapper' => null,
        'views' => array(
            'page' => 'tablestore_page'
        )
    );

    public function init() {
        $this->db = $this->ow->db;
    }

    public function delete($id) {
        // TODO: Implement delete() method.
        throw new Exception('Not implemented');
    }

    public function fetch($params = array()) {

        $defaults = array(
            '_fields' => null,
            '_eager' => true,
            '_inner' => array()
        );

        // Accept params in querystring form
        if (!is_array($params)) {
            $arr = array();
            parse_str($params, $arr);
            $params = $arr;
        }

        $params = array_merge($defaults, $params);


        // TODO: Implement fetch() method.
        throw new Exception('Not implemented');
    }

    public function get($id) {
        // TODO: Implement get() method.
        throw new Exception('Not implemented');
    }

    public function post($data) {
        // TODO: Implement post() method.
        throw new Exception('Not implemented');
    }

    public function put($id, $data) {
        // TODO: Implement put() method.
        throw new Exception('Not implemented');
    }

}