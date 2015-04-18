<?php
namespace Objectiveweb;

use \Exception;

abstract class Handler {

    public $id;
    public $params;

    protected $ow;

    protected $defaults = array();
    protected $with = array();
    protected $views = array();

    public function __construct($ow, $id, $params = array()) {
        $this->id = $id;
        $this->ow = $ow;

        if(!empty($params['with'])) {
            foreach($params['with'] as $service) {
                $this->with[$service] = new $service($this);
            }

            unset($params['with']);
        }

        if(!empty($params['views'])) {
            foreach($params['views'] as $view) {
                $this->views[$view] = $view;
            }
        }

        $this->params = array_merge($this->defaults, $params);

        $this->init();
    }


    public function apply_filters($method, $id, $data = null) {

        foreach ($this->with as $filter) {

            if (is_callable(array($filter, $method))) {
                $data = $filter->$method($id, $data);
            }

        }

        return $data;
    }

    /**
     * @param $id
     * @throws Exception
     * @return boolean
     */
    public function delete($id) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @param array $params
     * @throws Exception
     * @return array
     */
    public function fetch($params = array()) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @param $id
     * @throws Exception
     * @return array $resource
     */
    public function get($id, $params = array()){
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @param $data
     * @throws Exception
     * @return mixed
     */
    public function post($data){
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @param $data
     * @throws Exception
     * @return mixed
     */
    public function put($id, $data){
        throw new Exception('Method not allowed', 405);
    }

    public abstract function init();
}