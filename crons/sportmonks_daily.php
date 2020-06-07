<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require ABSPATH . 'core/config/database.php';
require ABSPATH . 'core/models/sportmonks_model.php';

$api = '046M5WZQ5qd5ykdZl49QMSEawxflH8oVCiYCLxJAqOzZJKRYz81lZxRKxGeJ';


$pdo = connection('open');
$prepare = $pdo->prepare('SELECT id FROM sportmonks_seasons WHERE is_current_season = 1 AND league_id = 8 limit 1');
$prepare->execute();
$fetch = $prepare->fetchAll(PDO::FETCH_ASSOC);

continentsToDb($pdo,$api);
countriesToDb($pdo,$api);
leaguesToDb($pdo,$api);
seasonsToDb($pdo,$api);


standingsToDb($pdo,$api,$fetch[0]['id']);
$pdo = connection('close');
?>
