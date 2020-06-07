<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
date_default_timezone_set('Europe/London');
require ABSPATH . 'config/database.php';
require 'functions.php';
$pdo = connection('open');

	$export = array();
	$season = getCurrentSeason($pdo);
	$season_id = $season['id'];

	$stmt = $pdo->prepare("select * from sportmonks_teams where country_id = 462 group by name");
	$stmt->execute();

	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$tips = array('over','under','gfover','gfunder','gaover','gaunder','btts-yes','btts-no','cs-yes','cs-no','1x2','wtn');
	$venues = array('overall','home','away');
	$searches = array(0.5,1,1.5,2,2.5,3,3.5,4,4.5,5);
	foreach ($fetch as $team) {
		
		$acronym = $team['mapped_teamname'];
		
		if(!is_null($acronym)){
		$teamid = $team['id'];

		//if(strtotime($team['checked_games']) < strtotime($team['team_last_game_date']) || $team['checked_games'] === NULL){
		$last_game = getTeamLastX($pdo,$teamid,1,$season_id)[0];
		$last_date = $last_game['date_time'];
		//var_dump($last_date);
		foreach($venues as $venue){

			if($venue == 'home'){
				$last6 = getTeamLastXAtHome($pdo,$teamid,6,$season_id);
			}
			elseif($venue == 'away'){
				$last6 = getTeamLastXAtAway($pdo,$teamid,6,$season_id);
			}
			else{
				$last6 = getTeamLastX($pdo,$teamid,6,$season_id);
			}
				
			$last6Arr = array();
			foreach ($last6 as $value) {
				$last6Arr[] = getLast6Array($value,$pdo);
			}
		
			$last6Arr = json_encode($last6Arr,JSON_UNESCAPED_SLASHES);
		
			foreach($tips as $tip) {
				
				
				$prepare = $pdo->prepare('INSERT INTO teamstats(teamid,tip,mapname,last6,roi,venue,search) 
				VALUES(:id,:tip,:mapname,:last6,:roi,:venue,:search) 
				ON DUPLICATE KEY UPDATE
				mapname=:mapname,last6=:last6,roi=:roi');
				
				if($tip == 'over' || $tip == 'under' || $tip == 'gfover' || $tip == 'gfunder' || $tip == 'gaover' || $tip == 'gaunder') {
					foreach($searches as $search){
						//var_dump("SEEEEEEEEEEEEEEEEEEEEEEARCHHHHEEER: " . $search);
						$roi = getROI($pdo,$teamid,$season_id,$tip,$search,$venue,6)['roi'];
						echo "<br/>";
						//var_dump($acronym . " " . $roi);
						echo "<br/>";
						$prepare->execute(array(':id'=>$teamid,':tip'=>$tip,':mapname'=>$acronym,':last6'=>$last6Arr,':roi'=>$roi,':venue'=>$venue,':search'=>$search));	
					}
				}
				else{
					$roi = getROI($pdo,$teamid,$season_id,$tip,0.0,$venue,6)['roi'];
					$prepare->execute(array(':id'=>$teamid,':tip'=>$tip,':mapname'=>$acronym,':last6'=>$last6Arr,':roi'=>$roi,':venue'=>$venue,':search'=>0.0));
				}
			}
		}
		
		$insert = $pdo->prepare('UPDATE sportmonks_teams SET checked_games = ? WHERE id=?');
		$insert->execute(array($last_date,$teamid));
		//}
		}
	}


ob_end_flush();
?>