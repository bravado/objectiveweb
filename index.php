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

// Load ObjectiveWeb
require_once "_init.php";

// Parse the request
$pattern = '/\/?(\w*)\/?([-\w]*)?\/?(.*)?/';

preg_match($pattern, $_SERVER['PATH_INFO'], $request);

// $request[0] = full path
// $request[1] = domain
// $request[2] = id
// $request[3] = attachment
//print_r($request);
try {

    // /domain/id ou /domain/id/attachment
    if ($request[2]) {

        // Object deve existir
        $object = get($request[1], $request[2]);

        if (!$object) {
            respond(array('error' => 'not_found', 'reason' => 'missing'), 404);
        }

        if ($request[3]) { // /domain/id/attachment
            switch($_SERVER['REQUEST_METHOD']) {
                case 'PUT':

                    // TODO verificar pelo type se precisa de base64
                    // TODO otimizar isto
                    $data = base64_decode(substr(file_get_contents("php://input"), 22) );

                    // TODO verificar se deve rolar pack() da imagem (binario flash)
                    // $data = pack('H*', file_get_contents("php://input"));
                    attach($request[1], $request[2], array(
                                          'name' => $request[3],
                                          'type' => $_SERVER['CONTENT_TYPE'],
                                          'data' => $data));
                    break;
                default:
                    throw new Exception(_('Method not allowed'), 405);
                    break;
            }
        }
        else { // /domain/id

            switch ($_SERVER['REQUEST_METHOD']) {

                case 'GET':
                    respond($object);
                    break;
                case 'POST':
                    // TODO implementar anexos
                    throw new Exception('POST Attachments Not implemented', 500);
                case 'PUT':
                    $data = parse_post_body();
                    write($request[1], $request[2], $data);
                    break;
                default:
                    throw new Exception(_('Method not allowed'), 405);
                    break;
            }
        }

    }
    // /domain
    elseif ($request[1]) {


        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':

                // TODO verificar ACLs
                respond(fetch($request[1], $_GET));
                break;
            case 'POST':

                $data = parse_post_body();

                $oid = create($request[1], $data);

                respond(array("ok" => true, "_id" => $oid));
                break;
            default:
                throw new Exception(_('Method not allowed'), 405);
                break;
        }
    }
    else {
        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                respond(array('objectiveweb' => 'welcome', 'version' => OBJECTIVEWEB_VERSION));
                break;
            case 'POST':
                throw new Exception('Not implemented', 500);
                break;
            default:
                throw new Exception(_('Method not allowed'), 405);
        }

    }
}
catch (Exception $ex) {
    error_log($ex->getTraceAsString());
    respond(array('error' => $ex->getMessage()), $ex->getCode());
}


