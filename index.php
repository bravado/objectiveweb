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

try {
    include "_init.php";
}
catch (Exception $ex) {
    header('HTTP/1.0 500 Internal Server Error');
    exit(json_encode(array("error" => $ex->getMessage())));
}

// default GET parameters
$defaults = array(
    'domain' => 'content');

$params = array_merge($defaults, $_GET);


$domain = get_domain($params['domain']);

header('Content-type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if (!empty($params['oid'])) {
            respond(array('error' => 'URL Parameters not allowed here'), 405);
        }

        try {

            $data = json_decode(file_get_contents('php://input'), true);

            $oid = $domain->create($data);

            respond(array('oid' => $oid));
        }
        catch (Exception $ex) {
            respond(array('error' => $ex->getMessage()), $ex->getCode());
        }

        break;
    case 'GET':
        if (empty($params['oid'])) {
            $results = $domain->fetch();
            echo json_encode($results);
        }
        else {
            $object = $domain->get($params['oid']);

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
                $domain->write($params['oid'], $data);
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
            $domain->delete($data['oid']);
        }
        catch(Exception $ex) {
            respond(array('error' => $ex->getMessage()), $ex->getCode());
        }
        break;
}
