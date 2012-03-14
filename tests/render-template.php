<?php
/**
 *
 *
 * User: guigouz
 * Date: 13/03/12
 * Time: 22:08
 */
 
include "../_init.php";
register_domain('tests', array(
    'handler' => 'FileStore',
    'root' => dirname(__FILE__)
));

?>

<html>
<head>
    <title>Render Template Test</title>
</head>
<body>

<p>This test renders the file "render-template-test.html" which lists all files on this directory (I defined a "tests" domain with this directory as root - see the source)</p>

<?php render('render-template-test.html', array('var1' => 1, 'var2' => 2)); ?>
</body>


</html>