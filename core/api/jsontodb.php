<?php
ob_start();

header("Access-Control-Allow-Origin: *");

error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
date_default_timezone_set('Europe/London');
require ABSPATH . 'config/database.php';
require 'functions.php';


$pdo = connection('open');

function getTeamStats($pdo){
	$type = 'over';
	$limit = 20;
	$search = 0.0;
	$venue = '';
	$value = '';

	$type = isset($_GET['type']) ? $_GET['type'] : $type;
	$venue = isset($_GET['venue']) ? $_GET['venue'] : $venue;
	$value = isset($_GET['value']) ? $_GET['value'] : $value;
	$limit = isset($_GET['limit']) ? $_GET['limit'] : $limit;
	$search = isset($_GET['search']) ? $_GET['search'] : $search;
	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];

	$export = array();

		$stmt = $pdo->prepare("select * from sportmonks_teams where country_id = 462 group by name");
		$stmt->execute();

		$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

	
		foreach ($fetch as $team) {

			//if(strtotime($team['checked_games']) < strtotime($team['team_last_game_date']) || $team['checked_games'] === NULL){
			$teamname = $team['name'];
			$teamlogo = $team['logo_path'];
			$acronym = getTeam($pdo,$team['id'])['mapped_teamname'];
			$team_id = $team['id'];	 


			if(!is_null($acronym)){
				
				$teammp = getTeamMP($pdo,$season_id,$team_id)['mp'];

				//var_dump($teammp);
				if($venue == 'home'){
					$teammp = getTeamMPHome($pdo,$season_id,$team_id)['mp'];
				}
				if($venue == 'away'){
					$teammp = getTeamMPAway($pdo,$season_id,$team_id)['mp'];
				}
				if($teammp === null) $teammp = 0;
				$totalrounds = getTotalRounds($pdo,$season_id)['name'];
				
				
				if($venue == 'home'){
					$stmt = $pdo->prepare("select * from sportmonks_fixtures where status = 'FT' and league_id = 8 and season_id = :season and localteam_id = :team order by date_time DESC LIMIT 1");
				}
				if($venue == 'away'){
					$stmt = $pdo->prepare("select * from sportmonks_fixtures where status = 'FT' and league_id = 8 and season_id = :season and visitorteam_id = :team order by date_time DESC LIMIT 1");
				}
				if($venue == 'overall'){
					$stmt = $pdo->prepare("select * from sportmonks_fixtures where status = 'FT' and league_id = 8 and season_id = :season and (localteam_id = :team or visitorteam_id = :team) order by date_time DESC LIMIT 1");			
				}
				 
				$stmt->execute(array(':season'=>$season_id,':team'=>$team['id']));

				$teamstat = getTeamRoi($pdo,$team_id,$type,$venue,$search);
				$last6Arr = $teamstat['last6'];

				$roi = getROI($pdo,$team_id,$season_id,$type,$search,$venue);
				$roi6 = getROI($pdo,$team_id,$season_id,$type,$search,$venue,6);

				$score_counter = $roi['score_counter'];
				$roi = $roi['roi'];
				


				$MP = $score_counter . '/' . $teammp;
				if($score_counter > 0 and $teammp > 0 )
						$success_score = ($score_counter / $teammp) * 100;
				else{
					$success_score = '0';
				}
				$success_score = round($success_score,2);

				$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
				foreach ($fetch as $row) {
					//var_dump($row);
					$nextmatch = getNextMatch($pdo,$team_id,$row['date']);
					if($nextmatch['localteam_id'] == $team_id){
						$nextmatch_name = getTeam($pdo,$nextmatch['visitorteam_id']);
					}
					else{
						$nextmatch_name = getTeam($pdo,$nextmatch['localteam_id']);
					}
					$goals = $row['localteam_score'];
					$op_goals = $row['visitorteam_score'];

					if($team['id'] == $row['visitorteam_id']){
						$goals = $row['visitorteam_score'];
						$op_goals = $row['localteam_score'];
					}
					
					$total = $goals + $op_goals;

					$export[] = array('team'=>array('id'=>$team_id,'name'=>$teamname, 'logo'=>$teamlogo, 'roi'=>$roi, 'roi6'=>$roi6, 'acronym'=>$acronym, 
					'last6' => $last6Arr, 'MP' => $MP, 'success_score'=>$success_score,
					'next_match'=>array('match'=>$nextmatch_name,
					'date'=>date('l d F',strtotime($nextmatch['date_time'])),'time'=>date('H:i',strtotime($nextmatch['date_time'])),
					'logo'=>$nextmatch_name['logo_path'])));
				}
			}	
		}
	//}
	$export = json_encode($export);
	$prepare = $pdo->prepare('INSERT INTO baby(id,tip,venue,json,search) 
	VALUES(:id,:tip,:venue,:json,:search) 
	ON DUPLICATE KEY UPDATE
	json = :json');
	$prepare->execute(array(':id'=>'teamstats',':tip'=>$type,':venue'=>$venue,':json'=>$export,':search'=>$search));
}


function getAllTeamsByWins($pdo){
	$export = array();
	$season = getCurrentSeason($pdo);
	$season_id = $season['id'];
	$type = "";
	$venue = "";
	$type = isset($_GET['type']) ? $_GET['type'] : $type;
	$venue = isset($_GET['venue']) ? $_GET['venue'] : $venue;

	$stmtx = $pdo->prepare("select * from sportmonks_teams where country_id = 462 group by name");
	$stmtx->execute();

	$fetchx = $stmtx->fetchAll(PDO::FETCH_ASSOC);
	//var_dump($fetch);
	foreach ($fetchx as $team) {

		//if(strtotime($team['checked_games']) < strtotime($team['team_last_game_date']) || $team['checked_games'] === NULL){
		$acronym = getTeam($pdo,$team['id'])['mapped_teamname'];

		if($acronym != ""){
			echo '<br/>' . $acronym;
			$standings = isset(getTeamStandings($pdo,$team['id'])[0]) ? getTeamStandings($pdo,$team['id'])[0] : null;
			$position = $standings['position'];
			if($venue == 'home'){
				$played = getTeamMPHome($pdo,$season_id,$team['id'])['mp'];
				$matches = getTeamGoalsHome($pdo,$team['id'],$season_id);
			}
			elseif($venue == 'away'){
				$played = getTeamMPAway($pdo,$season_id,$team['id'])['mp'];
				$matches = getTeamGoalsAway($pdo,$team['id'],$season_id);
			}
			else{
				$played = getTeamMP($pdo,$season_id,$team['id'])['mp'];
				$matches = getTeamGoals($pdo,$team['id'],$season_id);			
			}

			
			$roi = getROI($pdo,$team['id'],$season_id,'1x2',0,$venue)['roi'];
			$roi_draws = getROI($pdo,$team['id'],$season_id,'1x2-draws',0,$venue)['roi'];
			$roi_loss = getROI($pdo,$team['id'],$season_id,'1x2-loss',0,$venue)['roi'];

			$roi_6 = getROI($pdo,$team['id'],$season_id,'1x2',0,$venue,6)['roi'];
			$roi_draws_6 = getROI($pdo,$team['id'],$season_id,'1x2-draws',0,$venue,6)['roi'];
			$roi_loss_6 = getROI($pdo,$team['id'],$season_id,'1x2-loss',0,$venue,6)['roi'];

			echo "ROI: " . $roi . '<br/>';
			$scored = 0;
			$conceded = 0;
			$league_id = 0;
			
			foreach($matches as $match){
				//var_dump($match);
				$league_id = $match['league_id'];
				if($league_id == 8){
					if($team['id'] == $match['localteam_id']){
						$scored += (int)$match['localteam_score'];
						$conceded += (int)$match['visitorteam_score'];
					}
					
					if($team['id'] == $match['visitorteam_id']){
						$scored += (int)$match['visitorteam_score'];
						$conceded += (int)$match['localteam_score'];
					}
				}
			}
			
	        if($league_id == 8){
			$gd = $scored - $conceded;
			
			if($venue == 'home'){
				$winscore = getTeamWinsHome($pdo,$team['id'],$season_id);
				$drawscore = getTeamDrawsHome($pdo,$team['id'],$season_id);
				$losscore = getTeamLossesHome($pdo,$team['id'],$season_id);
			}
			elseif ($venue == 'away') {
				$winscore = getTeamWinsAway($pdo,$team['id'],$season_id);
				$drawscore = getTeamDrawsAway($pdo,$team['id'],$season_id);
				$losscore = getTeamLossesAway($pdo,$team['id'],$season_id);
			}
			else{
				$winscore = getTeamWins($pdo,$team['id'],$season_id);
				$drawscore = getTeamDraws($pdo,$team['id'],$season_id);
				$losscore = getTeamLosses($pdo,$team['id'],$season_id);
			}
			$wins = 0;
			$draws = 0;
			$losses = 0;
			
			foreach ($winscore as $value) {
				$wins++;
			}

			if($wins > 0 && $played > 0){

				$winperc = ($wins / $played) * 100;
			}
			else{
				$winperc = 0;
			}

			foreach ($drawscore as $value) {
				$draws++;
			}

			if($draws > 0 && $played > 0){

				$drawperc = ($draws / $played) * 100;
			}
			else{
				$drawperc = 0;
			}

			foreach ($losscore as $value) {
				$losses++;
			}

			if($losses > 0 && $played > 0){

				$lossperc = ($losses / $played) * 100;
			}
			else{
				$lossperc = 0;
			}
			if($venue == 'home'){
				$last6 = getTeamLastXAtHome($pdo,$team['id'],6,$season_id);
			}
			elseif ($venue == 'away') {
				$last6 = getTeamLastXAtAway($pdo,$team['id'],6,$season_id);
			}
			else{
				$last6 = getTeamLastX($pdo,$team['id'],6,$season_id);
			}

			
			$last6WinArr = array();
			$last6DrawArr = array();
			$last6LossArr = array();
			
			foreach ($last6 as $value) {
					$goals2 = $value['localteam_score'];
					$op_goals2 = $value['visitorteam_score'];
					$opteam = getTeam($pdo,$value['visitorteam_id']);
					$opname = $opteam['name'];
					$opacro = $opteam['mapped_teamname'];
					$opdate = date('l d F',strtotime($value['date']));
					$optime = date('H:i',strtotime($value['date_time']));
					$datetime = new DateTime($value['date_time']);
					$isodate = $datetime->format(DateTime::ATOM); // Updated ISO8601

					if($team['id'] == $value['visitorteam_id']){
						$goals2 = $value['visitorteam_score'];
						$op_goals2 = $value['localteam_score'];
						$opteam = getTeam($pdo,$value['localteam_id']);
						$opname = $opteam['name'];
						$opacro = $opteam['mapped_teamname'];
						$opdate = date('l d F',strtotime($value['date']));
						$optime = date('H:i',strtotime($value['date_time']));
					}

				$homegetteam = getTeam($pdo,$value['localteam_id']);
				$awaygetteam = getTeam($pdo,$value['visitorteam_id']);
				
				$hometeam6 = $homegetteam['name'];
				$awayteam6 = $awaygetteam['name'];
				
				$homeacro6 = $homegetteam['mapped_teamname'];
				$awayacro6 = $awaygetteam['mapped_teamname'];			
				
				$teamsArr = array('hometeam'=>array('id'=>$value['localteam_id'],'name'=>$hometeam6,'acro'=>$homeacro6),
								  'awayteam'=>array('id'=>$value['visitorteam_id'],'name'=>$awayteam6,'acro'=>$awayacro6));
				
				if($venue == 'home' || $venue == 'away'){
					if($value['localteam_id'] == $team['id'] and $venue == 'home'){
						$last6WinArr[] = array('success'=>isWin($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6DrawArr[] = array('success'=>isDraw($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6LossArr[] = array('success'=>isLoss($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
					}

					if($value['visitorteam_id'] == $team['id'] and $venue == 'away'){
						$last6WinArr[] = array('success'=>isWin($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6DrawArr[] = array('success'=>isDraw($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6LossArr[] = array('success'=>isLoss($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
					}	
				}
				else{
					if($value['localteam_id'] == $team['id']){
						$last6WinArr[] = array('success'=>isWin($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6DrawArr[] = array('success'=>isDraw($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6LossArr[] = array('success'=>isLoss($value['localteam_score'],$value['visitorteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
					}
					else{
						$last6WinArr[] = array('success'=>isWin($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6DrawArr[] = array('success'=>isDraw($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
						$last6LossArr[] = array('success'=>isLoss($value['visitorteam_score'],$value['localteam_score']),$teamsArr,array('date'=>$opdate,'time'=>$optime,'isodate'=>$isodate,
							'home_score'=>$value['localteam_score'],'away_score'=>$value['visitorteam_score'],'home_id'=>$value['localteam_id'],'away_id'=>$value['visitorteam_id']));
					}	
				}
			}
		}

		$nextmatch = getNextMatch($pdo,$team['id'],date('Y-m-d'));
		$nextdatetime = new DateTime($nextmatch['date_time']);
		$nextisodate = $nextdatetime->format(DateTime::ATOM); // Updated ISO8601		
		$nextmatchid = $nextmatch['id'];
		$nextteamid = 0;

		if($nextmatch['localteam_id'] == $team['id']){
			$nextmatch_name = getTeam($pdo,$nextmatch['visitorteam_id']);
			$nextteamid = $nextmatch['visitorteam_id'];
		}
		else{
			$nextmatch_name = getTeam($pdo,$nextmatch['localteam_id']);
			$nextteamid = $nextmatch['localteam_id'];
		}
		
		$nextmatch_name['acronym'] = $nextmatch_name['mapped_teamname'];
		if(isset($nextmatch_name['logo_path'])){
			$nextmatch_name['logo'] = $nextmatch_name['logo_path'];	
		}
		
		$nextmatch_name['isodate'] = $nextisodate;
		$nextmatch_name['matchid'] = $nextmatchid;
		$nextmatch_name['teamid'] = $nextteamid;
		//
		
		unset($nextmatch_name['short_code']);
		unset($nextmatch_name['mapped_teamname']);
		unset($nextmatch_name['logo_path']);

		if($played > 0){  
			$points = ($wins * 3) + $draws;
			$export[] = array('name'=>$team['name'],'teamPosition'=>(int)$position,'roi'=>$roi,'roi-draws'=>$roi_draws,'roi-loss'=>$roi_loss,'roi-6'=>$roi_6,'roi-draws-6'=>$roi_draws_6,'roi-loss-6'=>$roi_loss_6,'points'=>$points,
			'goals_difference'=>$gd,'scored'=>$scored,
			'conceded'=>$conceded,'wins'=>$wins,'draws'=>$draws,'losses'=>$losses,'winscore'=>round($winperc,2),
				'drawscore'=>round($drawperc,2),'losscore'=>round($lossperc,2),'played'=>(int)$played,'acronym'=>$acronym,'id'=>(int)$team['id'],
				'logo'=>$team['logo_path'],'lastXWins'=>$last6WinArr,'lastXDraws'=>$last6WinArr,'lastXLosses'=>$last6LossArr,
				'next'=>$nextmatch_name);
		}

		}
		//}
	}

	$export = json_encode($export);
	$prepare = $pdo->prepare('INSERT INTO baby(id,tip,venue,json) 
	VALUES(:id,:tip,:venue,:json) 
	ON DUPLICATE KEY UPDATE
	json = :json');
	$prepare->execute(array(':id'=>'wins',':tip'=>$type,':venue'=>$venue,':json'=>$export));

	return $export;
}


$_GET['teamlist'] = '';
$_GET['teamstats'] = '';
$_GET['matches'] = '';
$_GET['wins'] = '';


//if( isset($_GET['matches']) ){
	
	//var_dump('test');
//}
 
//if( isset($_GET['wins']) ){
	$venue = array('overall','home','away');
	foreach ($venue as $ven) {
		$_GET['venue'] = $ven;
		getAllTeamsByWins($pdo);
	}
	
//} 


//if( isset($_GET['teamstats']) ){
	$tips = array('over','under','gfover','gfunder','gaover','gaunder','btts-yes','btts-no','cs-yes','cs-no');
	$search = array(0.5,1,1.5,2,2.5,3,3.5,4,4.5,5);
	$venue = array('overall','home','away');
	
	foreach ($tips as $tip) {
		$_GET['type'] = $tip;
		foreach ($venue as $ven) {
			$_GET['venue'] = $ven;

			if($tip == 'over' || $tip == 'under' || $tip == 'gfover' || $tip == 'gfunder' || $tip == 'gaover' || $tip == 'gaunder') {
			
			foreach ($search as $s) {
				$_GET['search'] = $s;
				getTeamStats($pdo);
			}
			
			}
			else{
				getTeamStats($pdo);
			}
		}
		echo '<br/>'.$tip;
	}
//}


ob_end_flush();
?>