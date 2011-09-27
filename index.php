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

<<<<<<< HEAD
=======
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
>>>>>>> 84c60c8656d21c3c72812cd22abf2d718533f0b9

$_prefix = '/index.php'; // TODO verificar mod_rewrite

F3::route("GET /", 'about_objectiveweb');
F3::route("GET $_prefix", 'about_objectiveweb');

F3::route("GET $_prefix/@domain/_@plugin", 'handle_domain_plugin');
F3::route("POST $_prefix/@domain/_@plugin", 'handle_domain_plugin');
F3::route("PUT $_prefix/@domain/_@plugin", 'handle_domain_plugin');

F3::route("GET $_prefix/@domain/@id", 'handle_get_object');
F3::route("POST $_prefix/@domain/@id", 'handle_add_attachment');
F3::route("PUT $_prefix/@domain/@id", 'handle_update_object');
F3::route("DELETE $_prefix/@domain/@id", 'handle_delete_object');

F3::route("GET $_prefix/@domain/@id/_@plugin", 'handle_object_plugin');
F3::route("POST $_prefix/@domain/@id/_@plugin", 'handle_object_plugin');
F3::route("PUT $_prefix/@domain/@id/_@plugin", 'handle_object_plugin');
F3::route("DELETE $_prefix/@domain/@id/_@plugin", 'handle_object_plugin');

F3::route("GET $_prefix/@domain/@id/@attachment", 'handle_get_attachment');
F3::route("POST $_prefix/@domain/@id/@attachment", 'handle_update_attachment');
F3::route("PUT $_prefix/@domain/@id/@attachment", 'handle_update_attachment');
F3::route("DELETE $_prefix/@domain/@id/@attachment", 'handle_delete_attachment');

F3::route("POST $_prefix/@domain", 'handle_create_object');
F3::route("GET $_prefix/@domain", 'handle_get_domain');


F3::run();

function about_objectiveweb() {
    respond(ow_version());
}

function handle_get_domain() {
    respond(fetch(F3::get('PARAMS["domain"]'), $_GET));
}

function handle_domain_plugin() {
    $domain = get_domain(F3::get('PARAMS["domain"]'));

    switch(F3::get('PARAMS["plugin"]')) {
        case 'schema':
            respond($domain->schema());
            break;
        default:
            throw new Exception( F3::get('PARAMS["plugin"]'));
    }

}

function handle_get_object() {
    $object = get(F3::get('PARAMS["domain"]'), F3::get('PARAMS["id"]'));
    if (!$object) {
        respond(array('error' => 'not_found', 'reason' => 'missing'), 404);
    }
    else {
        respond($object);
    }
}

function handle_create_object() {
    $data = parse_post_body();

    $oid = create(F3::get('PARAMS["domain"]'), $data);

    respond(array("ok" => true, "_id" => $oid));
}

function handle_update_object() {
    $data = parse_post_body();
    write(F3::get('PARAMS["domain"]'), F3::get('PARAMS["id"]'), $data);
}





