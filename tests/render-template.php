<?php
/**
 *
 *
 * User: guigouz
 * Date: 13/03/12
 * Time: 22:08
 */
 
define('TEMPLATES_ROOT', dirname(__FILE__));
include "../_init.php";


?>

<html>
<head>
    <title>Oi</title>
</head>
<body>

<?php render('render-template-test.html', array('var1' => 1, 'var2' => 2)); ?>
</body>


</html>