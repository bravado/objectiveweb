<?php
/**
 *
 *
 * User: guigouz
 * Date: 25/09/12
 * Time: 17:53
 */

include "../_init.php";
define('ATTACHMENT_HASHDEPTH', 2);

// 12345
$real_filename = OW_CONTENT.'/domain/1/2/12345/test.txt';
echo attachment_filename('domain', "12345", 'test.txt') == $real_filename;

echo "<br>";
// 12
$real_filename = OW_CONTENT.'/domain/1/2/12/test.txt';
echo attachment_filename('domain', '12', 'test.txt') == $real_filename;

echo "<br>";
// 1
$real_filename = OW_CONTENT.'/domain/0/1/1/test.txt';
echo attachment_filename('domain', '1', 'test.txt') == $real_filename;
