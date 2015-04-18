<?php
/**
 * Objectiveweb-next Controller
 *
 * User: guigouz
 * Date: 5/18/14
 * Time: 2:05 PM
 *
 */

require "../src/bootstrap.php";

use Objectiveweb\Router;

defined('OW_CONFIG') || define('OW_CONFIG', ROOT.'/ow-config.php');

Router::route("GET /?", function() {
    return ow()->version();
});

Router::GET('/([a-z][a-z0-9_]*)/?', 'fetch');

Router::POST('/([a-z][a-z0-9_]*)/?', "post");

Router::PUT('/([a-z][a-z0-9_]*)/([\w-.\ ]+)?', "put");

Router::DELETE('/([a-z][a-z0-9_]*)/([\w-.\ ]+)?', "delete");

//Router::route("GET /([a-z][a-z0-9_]*)/?", 'fetch');
