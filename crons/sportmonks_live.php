<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require ABSPATH . 'core/config/database.php';
require ABSPATH . 'core/models/sportmonks_model.php';

$api = '046M5WZQ5qd5ykdZl49QMSEawxflH8oVCiYCLxJAqOzZJKRYz81lZxRKxGeJ';

$pdo = connection('open');



liveToDb($pdo,$api);


//getAllFixtures($pdo,$api); 

$pdo = connection('close');
?>