<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require ABSPATH . 'core/config/database.php';
require ABSPATH . 'core/parsers/538.php';

$file = ABSPATH. 'core/feeds/538/fixtures.csv';
get538();
$pdo = connection('open');
parseCSV($file,$pdo);
$pdo = connection('close');
?>
