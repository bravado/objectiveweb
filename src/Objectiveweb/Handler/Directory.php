<?php

namespace Objectiveweb\Handler;

use Objectiveweb\Handler;

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

    public function setup($params = array()) {
        $this->gacl->add_object_section('System', 'system', 0, 0, 'aco');
        $this->gacl->add_object_section('Users', 'users', 0, 0, 'aro');
        $this->gacl->add_object('users', 'Anonymous', 'anonymous', 0, true, 'aro');

        return true;
    }

    public function get($user, $params) {
        if($user == 'setup') {
            return $this->setup($params);
        }

        $id = $this->gacl->get_object_id('users', $user, 'aro');

        return $this->gacl->get_object_data($id, 'aro')[0];

    }

    public function fetch() {
        // TODO listar todos os users + sections do ARO
        $q = $this->gacl->get_objects('users', 0, 'aro');

        return $q['users'];
    }

    public function post($data) {
        $username = trim(strtolower($data['username']));

        // TODO criar ARO na section Users (section = group no json ?)
        // TODO senha ?
        $id = $this->gacl->add_object('users', $data['profile'], $username, 0, false, 'aro');

        if($id === FALSE) {
            throw new \Exception('Error adding user', 500);
        }
        elseif($id === TRUE) {
            throw new \Exception('User already exists', 409);
        }

        return array('id' => $username);
    }

    public function put($user, $data) {

        $id = $this->gacl->get_object_id('users', $user, 'aro');

        if($id === FALSE) {
            throw new \Exception('User not found', 404);
        }

        if($this->gacl->edit_object(
                $id,
                'users',
                $data,
                $user,
                0,
                0,
                'aro') === FALSE) {
            throw new \Exception('Error updating user', 500);
        }

        return array('id' => $user);
    }

    public function delete($user) {
        $id = $this->gacl->get_object_id('users', $user, 'aro');

        if($this->gacl->del_object($id, 'aro', true) === FALSE) {
            throw new \Exception('Error deleting entry', 500);
        }

        return array('id' => $user);
    }
}