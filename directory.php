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

        if(!empty($_GET['uid'])) {
            // UPDATE

            if($data['uid'] != $_GET['uid']) {
                // RENAME
            }
            else {

            }


        }
        else {
            // New directory item
        }


        // TODO verificar permissões

        directory_write($_POST['uid'], $_POST['resource']);

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
return;
$oid = ow_write('ow_directory', 'test2',
                array('value' => array('nome' => 'guigouz', 'tel' => '1234'), 'extrafield' => 700));

echo "wrote $oid";


// extend to another table

$oid = ow_write('ow_directory', 'test47',
                array('value' => array('oi', 'tchau', 'até'), 'matricula' => '777', '`schema`' => 'aluno'),
                array(
                     'extends' => array('alunos' => array('matricula')),
                     'versioning' => true
                ));

echo "wrote extended $oid";


ow_directory_add(array(
                      'schema' => 'user',


                 ));