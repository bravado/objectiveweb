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

header('Content-type: application/json');

// Load ObjectiveWeb
require_once "_init.php";



// default GET parameters
$defaults = array(
    'oid' => false,
    'meta' => false,
    'domain' => 'content');
$params = array_merge($defaults, $_GET);

try {
    $domain = get_domain($params['domain']);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':

            $data = json_decode(file_get_contents('php://input'), true);

            // Add metadata
            if ($params['oid']) {
                // TODO verificar permissão do objeto
                $meta = $domain->meta($params['oid'], $data['meta_key'], $data['meta_value']);

                respond($meta);
            }

            // TODO verificar permissão para criação de objetos


            // TODO passar o owner (current_user)
            $oid = $domain->create($data);

            respond(array('oid' => $oid));

            break;
        case 'GET':
            if ($params['oid']) {

                // TODO verificar permissão de leitura deste objeto
                $object = $domain->get($params['oid']);

                if (!$object) {
                    respond(array('error' => "Object not found"), 404);
                }

                if ($params['meta']) {
                    $meta = $domain->meta($params['oid'], $params['meta']);
                    respond($meta);
                }
                else {
                    respond($object);
                }

            }
            else {
                // TODO verificar permissão de listagem (x) do dominio
                $results = $domain->fetch();
                echo json_encode($results);
            }
            break;
        case 'PUT':
            if ($params['oid']) {
                $data = json_decode(file_get_contents('php://input'), true);
                $domain->write($params['oid'], $data);
            }
            else {
                respond(array('error' => 'Invalid OID'), 405);
            }
            break;
        case 'DELETE':
            if ($params['oid']) {
                respond(array('error' => 'URL Parameters not allowed here'), 405);
            }


            $data = json_decode(file_get_contents('php://input'), true);


            // TODO verificar se tem permissão de DELETE neste objeto
            $domain->delete($data['oid']);
            break;
    }


}
catch (Exception $ex) {
    respond(array('error' => $ex->getMessage()), $ex->getCode());
}



