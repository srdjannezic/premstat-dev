<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require ABSPATH . 'core/config/database.php';
require ABSPATH . 'core/models/sportmonks_model.php';

$api = '046M5WZQ5qd5ykdZl49QMSEawxflH8oVCiYCLxJAqOzZJKRYz81lZxRKxGeJ';

$pdo = connection('open');

$date1=date("Y-m-d", strtotime("-40 days"));
$date2=date('Y-m-d', strtotime("+30 days"));

$sql = "select id from sportmonks_teams where country_id = 462";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

liveToDb($pdo,$api,true);

foreach($fetch as $team){
	fixturesToDb($pdo,$api,$date1,$date2,$team['id']);
}

function getAllFixtures($pdo,$api){
	$yearsCount = (int)date('Y') - 2005;
	

	$sql = "select id from sportmonks_teams where country_id = 462";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach($fetch as $team){
		//var_dump($team['id']);
		for ($i=0; $i <= $yearsCount+1; $i++) { 
			$date1=date("Y-m-d", strtotime("+{$i} years", strtotime('2005-01-01')));
			$date2=date("Y-m-d", strtotime("+{$i} years", strtotime('2005-12-31')));
			fixturesToDb($pdo,$api,$date1,$date2,$team['id']);
		}
	}
}

//getAllFixtures($pdo,$api); 

$pdo = connection('close');
?>