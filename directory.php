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



if($_SERVER['REQUEST_METHOD'] == 'POST') {

    echo "oi";
    $data = file_get_contents('php://input');


    $data = json_decode($data, true);


    print_r($data);

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