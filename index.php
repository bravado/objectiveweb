<?php
/**
 * ObjectiveWeb
 *
 * Main REST endpoint
 *
 * User: guigouz
 * Date: 12/05/11
 * Time: 19:27
 */

include "_init.php";


$defaults = array(
    'domain' => 'content');


$params = array_merge($defaults, $_GET);

header('Content-type: application/json');


switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if (!empty($params['oid'])) {
            respond(array('error' => 'URL Parameters not allowed here'), 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $oid = ow_create($params['domain'], $data);

            respond(array('oid' => $oid));
        }
        catch (Exception $ex) {
            respond(array('error' => $ex->getMessage()), $ex->getCode());
        }

        break;
    case 'GET':
        if (empty($params['oid'])) {
            $results = ow_fetch($params['domain']);
            echo json_encode($results);
        }
        else {
            $object = ow_get($params['domain'], $params['oid']);

            if (!$object) {
                respond(array('error' => "Object not found"), 404);
            }
            else {
                respond($object);
            }
        }
        break;
    case 'PUT':
        if (empty($params['oid'])) {
            respond(array('error' => 'Invalid OID'), 405);
        }
        else {

            try {
                $data = json_decode(file_get_contents('php://input'), true);
                ow_write($params['domain'], $params['oid'], $data);
            }
            catch (Exception $ex) {
                respond(array('error' => $ex->getMessage()), $ex->getCode());
            }
        }
        break;
    case 'DELETE':
        if (!empty($params['oid'])) {
            respond(array('error' => 'URL Parameters not allowed here'), 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            ow_delete($params['domain'], $data['oid']);
        }
        catch(Exception $ex) {
            respond(array('error' => $ex->getMessage()), $ex->getCode());
        }
        break;
}
