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


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        $data = parse_post_body();

        // TODO validation
// TODO verificar permissões
        if(!empty($_GET['uid'])) {


            if($data['uid'] != $_GET['uid']) {
                // RENAME
                directory_rename($_GET['uid'], $data['uid']);
            }
            else {
                // UPDATE
                directory_update($data['uid'], $data);
            }

        }
        else {
            // New directory item
            directory_add($data['uid'], $data);
        }





    }
    catch(Exception $ex) {
        header('HTTP/1.0 500');
        exit($ex->getMessage());
    }
}
else {
    if (empty($_GET['uid'])) {
        // lista todos os ítens
        // TODO paginação já no esquema datatables

        echo json_encode(directory_list(), true);


    }
    else {
        $entry = directory_get($_GET['uid']);

        if(!$entry) {
            exit('Does not exist');
        }
        else {
            echo json_encode($entry);
        }
    }

}
