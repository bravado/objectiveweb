<?php

namespace Objectiveweb;

class Directory extends Handler {

    /** @var  $gacl \gacl_api */
    public $gacl;

    protected $views = array(
        'groups' => 'GroupManager'
    );

    public function init()
    {
        $this->gacl = new \gacl_api($this->params);
    }

    public function setup() {
        $this->gacl->add_object_section('System', 'system', 0, 0, 'aco');
        $this->gacl->add_object_section('Users', 'users', 0, 0, 'aro');
        $this->gacl->add_object('users', 'anonymous', 'Anonymous', 0, true, 'aro');
    }

    public function fetch() {
        // TODO listar todos os users + sections do ARO
    }

    public function post($data) {
        // TODO criar ARO na section Users (section = group no json ?)
    }

    public function put($id, $data) {
        // TODO atualizar dados da ARO
    }

    public function delete($id) {
        // TODO Excluir ARO
    }
}