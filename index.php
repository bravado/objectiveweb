<?php
/**
 * ObjectiveWeb
 *
 * Main Controller, which exposes all the configured domains
 *
 * User: guigouz
 * Date: 12/05/11
 * Time: 19:27
 */

// Load ObjectiveWeb
require_once "_init.php";

// Mandatory aboutbox
route('GET /?', 'ow_version');

// Views before attachments
// /domain/_view
route('GET /(\w*/_\w*/?.*)', 'fetch', $_GET);
//route('POST /(\w*)/_(\w*)/?', 'handle_view');
//route('PUT /(\w*)/_(\w*)/?', 'handle_view');

// Attachments before, because they may use php://stdin
route('POST /(\w+)/([\w-]+)?', 'handle_attachment_post');
route('POST /(\w+)/([\w-]+)/([\w-.\ ]+)', 'handle_attachment_post');
route('PUT /(\w+)/([\w-]+)/([\w-.\ ]+)', 'handle_attachment_post');
route('DELETE /(\w+)/([\w-]+)/([\w-.\ ]+)', 'handle_attachment_delete');
route('GET /(\w+)/([\w-]+)/([\w-.\ ]+)', 'handle_attachment_get');

$body = parse_post_body();

// /domain
route('GET /(\w+)/?', 'fetch', $_GET);
route('POST /(\w*)/?', 'post', $body);


// /domain/id
route('GET /(\w+)/([\w-]+)/?', 'get', $_GET);
route('PUT /(\w+)/([\w-]+)/?', 'put', $body);
route('DELETE /(\w+)/([\w-]+)/?', 'delete');

route('GET /(\w+)/([\w-]+)/_(\w+)', 'handle_plugin');
//route('POST /(\w*)/(\w*)/_(\w*)', 'handle_plugin');
//route('PUT /(\w*)/(\w*)/_(\w*)', 'handle_plugin');
//route('DELETE /(\w*)/(\w*)/_(\w*)', 'handle_plugin');


function handle_attachment_delete($domain, $id, $attachment) {
    attachment_delete($domain, $id, $attachment);
}

function handle_attachment_get($domain, $id, $attachment) {
    // TODO set header
    $fp = attachment_open($domain, $id, $attachment);
    fpassthru($fp);
    fclose($fp);
    exit;
}

function handle_attachment_post($domain, $id, $attachment_id = null) {
    $files = array();
    if ($attachment_id) {
        // TODO if $_FILES put($domain,$id, $attachment_id, fopen($_FILES['tmp_file']), $metadata);
        if(empty($_FILES)) {
            $files[] = attach($domain, $id, $attachment_id, $fp = fopen('php://input', "rb"), ATTACHMENT_OVERWRITE);
            fclose($fp);
        }
        else {
            throw new Exception('Not implemented', 500);
        }
    }
    else {
        foreach ($_FILES as $key => $fileinput) {
            if (!is_array($fileinput['name'])) {
                // single file

                $files[] = attach_local($domain, $id, $key, $fileinput['tmp_name'], ATTACHMENT_UNLINK);
            }
            else {
                // array of files
                foreach ($fileinput['name'] as $file => $filename) {

                    $files[] = attach_local($domain, $id, is_numeric($file) ? $fileinput['name'][$file] : $key, $fileinput['tmp_name'][$file], ATTACHMENT_UNLINK);
                }
            }
        }
    }

    respond($files);
}

function handle_plugin($domain, $id, $plugin) {
    if($plugin == 'attachments') {
        respond(attachment_list($domain, $id));
    }
    else {
        respond('Invalid plugin', 404);
    }
}

function handle_view($path) {
    fetch($path, $_GET);
}
