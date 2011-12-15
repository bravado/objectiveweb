<?php
/**
 * 
 * 
 * User: guigouz
 * Date: 03/07/11
 * Time: 22:32
 */

$urls = array("/", "/domain", "/domain/", "/domain/id", "/domain/id/", "/domain/id/attachment.jpg", "/domain/id/folder/attachment.jpg");

define('OW_URLPATTERN', '/\/(?P<domain>\w+)\/?(?P<id>\w+)?\/?(?P<attachment>.*)?/');

$pattern = '/\/?(\w*)\/?(\w*)?\/?(.*)?/';
$pattern = OW_URLPATTERN;
foreach($urls as $url) {

 preg_match($pattern, $url, $matches);
 echo "<pre>$url\n";
 print_r($matches);
 echo "</pre>";
}
