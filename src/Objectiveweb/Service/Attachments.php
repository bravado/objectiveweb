<?php

namespace Objectiveweb\Service;

class Attachments extends \Objectiveweb\Service {

    function get($id, $data) {

        if($id) {
            $data['_attachments'] = attachment_list($this->handler->id, $id);
        }

        return $data;
    }


    function service($id) {

        $connector = new \elFinderConnector(_attachments($this->handler->id, $id));
        $connector->run();
    }

}