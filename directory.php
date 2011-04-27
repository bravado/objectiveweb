<?php
/**
 * ObjectiveWeb
 *
 * Directory Service
 *
 * User: guigouz
 * Date: 14/04/11
 * Time: 01:05
 */


include "_init.php";

header('Content-type: application/json');

$schema = empty($_GET['schema']) ? null: $_GET['schema'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        $data = parse_post_body();

        // TODO validation
// TODO verificar permissões
        if(empty($_GET['oid'])) {


            // New directory item
            directory_add($data, $schema);
            
        }
        else {
            directory_update($_GET['oid'], $data, $schema);
        }





    }
    catch(Exception $ex) {
        header('HTTP/1.0 500');
        exit($ex->getMessage());
    }
}
else {



    if (empty($_GET['oid'])) {
        // lista todos os ítens
        // TODO paginação já no esquema datatables

        echo json_encode(directory_fetch($schema), true);


    }
    else {


        $entry = directory_get($_GET['oid'], $schema);

        if(!$entry) {
            exit('Does not exist');
        }
        else {
            echo json_encode($entry);
        }
    }

}
