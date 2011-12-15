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

// Obligatory aboutbox
route('GET /?', 'ow_version');

$body = parse_post_body();

// /domain
route('GET /(\w*)/?', 'fetch', $_GET);
route('POST /(\w*)/?', 'create', $body);

// /domain/_plugin
route('GET /(\w*)/_(\w*)/?', 'handle_domain_plugin');
route('POST /(\w*)/_(\w*)/?', 'handle_domain_plugin');
route('PUT /(\w*)/_(\w*)/?', 'handle_domain_plugin');

// /domain/id
route('GET /(\w*)/([\w-]*)/?', 'get');
route('POST /(\w*)/([\w-]*)/?', 'handle_add_attachments');
route('PUT /(\w*)/([\w-]*)/?', 'write', $body);
//route('DELETE /(\w*)/(\w*)/?', 'delete');

//route('GET /(\w*)/(\w*)/_(\w*)', 'handle_object_plugin');
//route('POST /(\w*)/(\w*)/_(\w*)', 'handle_object_plugin');
//route('PUT /(\w*)/(\w*)/_(\w*)', 'handle_object_plugin');
//route('DELETE /(\w*)/(\w*)/_(\w*)', 'handle_object_plugin');
//
//route('GET /(\w*)/(\w*)/(\w*)', 'get');
//route('POST /(\w*)/(\w*)/(\w*)', 'handle_update_attachment');
//route('PUT /(\w*)/(\w*)/(\w*)', 'handle_update_attachment');
//route('DELETE /(\w*)/(\w*)/(\w*)', 'handle_delete_attachment');


// Domain handlers
function handle_get_domain($domain) {
    respond(fetch($domain, $_GET));
}

function handle_domain_plugin($domain, $plugin) {
    switch($plugin) {
        case 'schema':
            respond($domain->schema());
            break;
        default:
            throw new Exception( F3::get('PARAMS["plugin"]'));
    }

}

// Object handlers
function handle_get_object($domain, $id) {
    $object = get($domain, $id);
    if (!$object) {
        respond(array('error' => 'not_found', 'reason' => 'missing'), 404);
    }
    else {
        respond($object);
    }
}

function handle_create_object($domain) {
    $data = parse_post_body();

    $oid = create($domain, $data);

    respond(array("ok" => true, "_id" => $oid));
}

function handle_update_object($domain, $id) {
    $data = parse_post_body();
    respond(array("ok" => true, "_id" =>write($domain, $id, $data) ));
}

function handle_delete_object($domain, $id) {
    
}
// Attachment handlers

function handle_get_attachment($domain, $id, $attachment) {
    respond(get($domain, $id, $attachment));
}

function handle_add_attachments() {
    // TODO verificar $_FILES e gravar anexos
}

function handle_update_attachment($domain, $id, $attachment) {
    $data = array(
        'name' => $attachment,
        'data' => file_get_contents('php://input')
        // TODO type header via content-type
    );


    respond(attach($domain, $id, $data));

}

