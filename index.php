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





