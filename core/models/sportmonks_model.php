<?php
require ABSPATH . 'core/parsers/sportmonks.php';

function continentsToDb($db,$api){
	$continents = parseSportMonks($api,'continents');

	foreach ($continents as $key => $continent) {
		$insert = $db->prepare('INSERT INTO sportmonks_continents (id,name) VALUES(?,?)
			ON DUPLICATE KEY UPDATE 
			id = ?,
			name= ?');
		$insert->execute(array($continent['id'],$continent['name'],$continent['id'],$continent['name']));
	}
	
}

function countriesToDb($db,$api){
	$countries = parseSportMonks($api,'countries');

	foreach ($countries as $key => $country) {
		$insert = $db->prepare('INSERT INTO sportmonks_countries (`id`, `name`, `continent`, `sub_region`, `world_region`, `fifa`, `iso`, `longitude`, `latitude`, `flag`) VALUES(?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE 
			`id`=?,`name`=?,`continent`=?,`sub_region`=?,`world_region`=?,`fifa`=?,`iso`=?,`longitude`=?,`latitude`=?,`flag`=?');
		$insert->execute(array($country['id'],$country['name'],$country['extra']['continent'],$country['extra']['sub_region'],$country['extra']['world_region'],$country['extra']['fifa'],$country['extra']['iso'],$country['extra']['longitude'],$country['extra']['latitude'],$country['extra']['flag'],$country['id'],$country['name'],$country['extra']['continent'],$country['extra']['sub_region'],$country['extra']['world_region'],$country['extra']['fifa'],$country['extra']['iso'],$country['extra']['longitude'],$country['extra']['latitude'],$country['extra']['flag']));
	}
}

function leaguesToDb($db,$api){
	$leagues = parseSportMonks($api,'leagues');

	foreach ($leagues as $key => $league) {
		$insert = $db->prepare('INSERT INTO sportmonks_leagues (`id`, `legacy_id`, `country_id`, `name`, `current_season_id`, `current_round_id`, `current_stage_id`, `live_standings`, `topscorer_goals`, `topscorer_assists`, `topscorer_cards`) VALUES(?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE 
			`id`=?, `legacy_id`=?, `country_id`=?, `name`=?, `current_season_id`=?, `current_round_id`=?, `current_stage_id`=?, `live_standings`=?, `topscorer_goals`=?, `topscorer_assists`=?, `topscorer_cards`=?');
		$insert->execute(array($league['id'],$league['legacy_id'],$league['country_id'],$league['name'],$league['current_season_id'],$league['current_round_id'],$league['current_stage_id'],$league['live_standings'],$league['coverage']['topscorer_goals'],$league['coverage']['topscorer_assists'],$league['coverage']['topscorer_cards'],$league['id'],$league['legacy_id'],$league['country_id'],$league['name'],$league['current_season_id'],$league['current_round_id'],$league['current_stage_id'],$league['live_standings'],$league['coverage']['topscorer_goals'],$league['coverage']['topscorer_assists'],$league['coverage']['topscorer_cards']));
	}
	
}


function seasonsToDb($db,$api){
	$seasons = parseSportMonks($api,'seasons');



	foreach ($seasons as $key => $season) {
		$insert = $db->prepare('INSERT INTO sportmonks_seasons (`id`, `name`, `league_id`, `is_current_season`, `current_round_id`, `current_stage_id`) VALUES(?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE 
			`id`=?, `name`=?, `league_id`=?, `is_current_season`=?, `current_round_id`=?, `current_stage_id`=?');
		$insert->execute(array($season['id'],$season['name'],$season['league_id'],$season['is_current_season'],$season['current_round_id'],$season['current_stage_id'],$season['id'],$season['name'],$season['league_id'],$season['is_current_season'],$season['current_round_id'],$season['current_stage_id']));
		 teamsToDb($db,$api,$season['id']);
		 roundsToDb($db,$api,$season['id']);
		 stagesToDb($db,$api,$season['id']);
	}
	
}


function getTeam($pdo,$id){
	$stmt = $pdo->prepare("select name,short_code,mapped_teamname,logo_path from sportmonks_teams where id = :id limit 1");
	$stmt->execute(array(':id'=>$id));
	$team = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$return = isset($team[0]) ? $team[0] : null;
	return $return;
}

function liveToDb($db,$api,$checknext=false){
	$live = parseSportMonks($api,'livescores/now');
	//$live = parseSportMonks($api,'test');
	//var_dump($live); 
	$export = array();
	$update = false;
	$writefile = false;
	$path = ABSPATH . 'crons/allowliveupdate.txt';
	
$checknext = true;
	//var_dump($live);
if($checknext == true){ //hourly cron update
		$prepare = $db->prepare('select * from sportmonks_fixtures where `date` = CURDATE() AND league_id = 8');
		$prepare->execute();
		$file = fopen($path,'w');
		
		$fetch = $prepare->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($fetch as $row){
			$gamestamp = strtotime($row['date_time']);
			$now = strtotime(date('Y-m-d H:i:s'));
			
			$division = $gamestamp - $now;
			$division = $division / ( 60 * 60 );
$division = (int)$division;
			var_dump($division);
			
			if($division <= 2 && $division >= -3){
				$writefile = true;
				break;
			}
			else{
				$writefile = false;
continue;
			}
		}
		if($writefile == true){
			fwrite($file,'1');
		}
		else{
			fwrite($file,'0');
		}
		fclose($file);
	}
	else{ //1 minute cron update
		$readfile = fopen($path,'r');
		$line = fgets($readfile);		//read for allow 1 min update
		if($line == '1'){
			$update = true;
		}
		else{
			$update = false;
		}
		
		var_dump($line);
		var_dump($update);
		fclose($readfile);
	}
	if($update || $checknext){
		echo "live";
		foreach ($live as $key=>$match) {
			if($match['league_id'] == 8){ //premier league
				$yellow_cards = 0;
				$red_cards = 0;
				$shots_total = 0;
				$shots_target = 0;
				$shots_inside = 0;
				$passes = 0;
				$attacks = 0;
				$attacks_dangerous = 0;
				$fouls = 0;
				$corners = 0;
				$posession = 0;
				
				$hometeam = getTeam($db,$match['localteam_id']);
				$awayteam = getTeam($db,$match['visitorteam_id']);
				$export[$key]['matchid'] = $match['id'];
				$export[$key]['hometeam'] = $hometeam['name'];
				$export[$key]['awayteam'] = $awayteam['name'];
				$export[$key]['hometeam_acronym'] = $hometeam['mapped_teamname'];
				$export[$key]['awayteam_acronym'] = $awayteam['mapped_teamname'];
				$export[$key]['status'] = $match['time']['status'];
				$export[$key]['time'] = $match['time']['minute'] . ":" . $match['time']['second'];
				$export[$key]['score_ft'] = $match['scores']['ft_score'];
				$export[$key]['score_ht'] = $match['scores']['ht_score'];
				$export[$key]['score_live'] = $match['scores']['localteam_score'] . "-" . $match['scores']['visitorteam_score'];
				$export[$key]['stadium_name'] = $match['venue']['data'];
			
				$ex_stat = array();
				foreach($match['stats']['data'] as $stats){
					$ex_stat[] = array('team_name'=>getTeam($db,$stats['team_id'])['name'],'yellow_cards'=>$stats['yellowcards'],
					'red_cards'=>$stats['redcards'],'shots_total'=>$stats['shots']['total'],'shots_on_target'=>$stats['shots']['ongoal'],
					'shots_inside'=>$stats['shots']['insidebox'],'passes'=>$stats['passes']['total'],
					'attacks'=>$stats['attacks']['attacks'],'attacks_dangerous'=>$stats['attacks']['dangerous_attacks'],
					'fouls'=>$stats['fouls'],'corners'=>$stats['corners'],'possession'=>$stats['possessiontime']);
				}
			
				$export[$key]['stats'] = $ex_stat;
				$export[$key]['events'] = $match['events']['data'];
				$export[$key]['goals'] = $match['goals'];
				$export[$key]['lineup'] = $match['lineup'];
			}
		}
	}
	
	$json =  json_encode($export,JSON_UNESCAPED_UNICODE);
	echo $json;
	$file2 = fopen(ABSPATH . 'crons/livescores.json', 'w');
	fwrite($file2, $json);
	fclose($file2);

}

function teamsToDb($db,$api,$season_id){
	$teams = parseSportMonks($api,'teams','','',$season_id);
	
	foreach($teams as $team){
		$insert = $db->prepare('INSERT INTO `sportmonks_teams`(`id`, `legacy_id`, `name`, `short_code`, `twitter`, `country_id`, `national_team`, `founded`, `logo_path`, `venue_id`, `mapped_teamname`)
		VALUES (?,?,?,?,?,?,?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
		`id`=?, `legacy_id`=?, `name`=?, `short_code`=?, `twitter`=?, `country_id`=?, `national_team`=?, `founded`=?, `logo_path`=?, `venue_id`=?, `mapped_teamname`=?');
		
		$mappedteamname = getMapName($db,$team['name']);
		
		$insert->execute(array($team['id'],$team['legacy_id'],$team['name'],$team['short_code'],$team['twitter'],$team['country_id'],$team['national_team'],$team['founded'],$team['logo_path'],$team['venue_id'],$mappedteamname,$team['id'],$team['legacy_id'],$team['name'],$team['short_code'],$team['twitter'],$team['country_id'],$team['national_team'],$team['founded'],$team['logo_path'],$team['venue_id'],$mappedteamname));
	}
}


function roundsToDb($db,$api,$season_id){
	$rounds = parseSportMonks($api,'rounds','','',$season_id);
	
	foreach($rounds as $round){
		$insert = $db->prepare('INSERT INTO `sportmonks_rounds`(`id`, `name`, `league_id`, `season_id`, `stage_id`, `start`, `end`)
		VALUES (?,?,?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
		`id`=?, `name`=?, `league_id`=?, `season_id`=?, `stage_id`=?, `start`=?, `end`=?');
		
		$insert->execute(array($round['id'],$round['name'],$round['league_id'],$round['season_id'],$round['stage_id'],$round['start'],$round['end'],$round['id'],$round['name'],$round['league_id'],$round['season_id'],$round['stage_id'],$round['start'],$round['end']));
	}
}

function stagesToDb($db,$api,$season_id){
	$stages = parseSportMonks($api,'stages','','',$season_id);
	
	foreach($stages as $stage){
		$insert = $db->prepare('INSERT INTO `sportmonks_stages`(`id`, `name`,`type`,`league_id`, `season_id`)
		VALUES (?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
		`id`=?, `name`=?,`type`=?, `league_id`=?, `season_id`=?');
		
		$insert->execute(array($stage['id'],$stage['name'],$stage['type'],$stage['league_id'],$stage['season_id'],$stage['id'],$stage['name'],$stage['type'],$stage['league_id'],$stage['season_id']));
	}
}

function standingsToDb($db,$api,$season_id){
	$standings = parseSportMonks($api,'standings','','',$season_id)[0];
	
	foreach($standings['standings']['data'] as $stand){
		//var_dump($stand);
		$insert = $db->prepare('INSERT INTO `sportmonks_standings`(`league_id`, `season_id`, `stage_id`, `stage_name`, `position`, `team_id`, `team_name`, `round_id`, `round_name`, `group_id`, `group_name`, `overall`, `home`, `away`, `totalx`, `resultx`, `pointsx`, `recent_form`, `statusx`)
		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
		`league_id`=?, `season_id`=?, `stage_id`=?, `stage_name`=?, `position`=?, `team_id`=?, `team_name`=?, 
		`round_id`=?, `round_name`=?, `group_id`=?, `group_name`=?, `overall`=?, `home`=?, `away`=?, `totalx`=?, 
		`resultx`=?, `pointsx`=?, `recent_form`=?, `statusx`=?');


		$insert->execute(array($standings['league_id'], $standings['season_id'], $standings['stage_id'], $standings['stage_name'], $stand['position'], $stand['team_id'], $stand['team_name'], $stand['round_id'], 
			$stand['round_name'], $stand['group_id'], $stand['group_name'], json_encode($stand['overall']), 
			json_encode($stand['home']), json_encode($stand['away']), json_encode($stand['total']), 
			$stand['result'], $stand['points'], $stand['recent_form'], $stand['status'], $standings['league_id'], $standings['season_id'], $standings['stage_id'], $standings['stage_name'], $stand['position'], $stand['team_id'], $stand['team_name'], $stand['round_id'], 
			$stand['round_name'], $stand['group_id'], $stand['group_name'], json_encode($stand['overall']), 
			json_encode($stand['home']), json_encode($stand['away']), json_encode($stand['total']), 
			$stand['result'], $stand['points'], $stand['recent_form'], $stand['status'] ) );
	}
}

function insertTeamLastGameDate($db,$teamid,$date){
	var_dump($teamid);
	$insert = $db->prepare('UPDATE sportmonks_teams SET team_last_game_date = ? WHERE id=?');

	$insert->execute(array($date,$teamid));
}


function fixturesToDb($db,$api,$from,$to,$team_id=''){
	$fixtures = parseSportMonks($api,'fixtures',$from,$to,'',$team_id);

		$insert = $db->prepare('INSERT INTO sportmonks_fixtures (`id`, `league_id`, `season_id`, `stage_id`, `round_id`, `group_id`, `aggregate_id`, `venue_id`, `referee_id`, `localteam_id`, `visitorteam_id`, `weather_report`, `commentaries`, `attendance`, `pitch`, `winning_odds_calculated`, `localteam_formation`, `visitorteam_formation`, `localteam_score`, `visitorteam_score`, `localteam_pen_score`, `visitorteam_pen_score`, `ht_score`, `ft_score`, `et_score`, `status`, `date_time`, `date`, `time`, `timestamp`, `timezone`, `minute`, `second`, `added_time`, `extra_minute`, `injury_time`, `localteam_coach_id`, `visitorteam_coach_id`, `localteam_position`, `visitorteam_position`, `leg`, `colors`, `deleted`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
`id`=?, `league_id`=?, `season_id`=?, `stage_id`=?, `round_id`=?, `group_id`=?, `aggregate_id`=?, `venue_id`=?, `referee_id`=?, `localteam_id`=?, `visitorteam_id`=?, `weather_report`=?, `commentaries`=?, `attendance`=?, `pitch`=?, `winning_odds_calculated`=?, `localteam_formation`=?, `visitorteam_formation`=?, `localteam_score`=?, `visitorteam_score`=?, `localteam_pen_score`=?, `visitorteam_pen_score`=?, `ht_score`=?, `ft_score`=?, `et_score`=?, `status`=?, `date_time`=?, `date`=?, `time`=?, `timestamp`=?, `timezone`=?, `minute`=?, `second`=?, `added_time`=?, `extra_minute`=?, `injury_time`=?, `localteam_coach_id`=?, `visitorteam_coach_id`=?, `localteam_position`=?, `visitorteam_position`=?, `leg`=?, `colors`=?, `deleted`=?
		');

	foreach ($fixtures as $key => $fix) {
		if($fix['league_id'] == 8) { //premier league
		$time = $fix['time'];
		$formation = $fix['formations'];
		$scores = $fix['scores'];
		$standings = $fix['standings'];
		$coaches = $fix['coaches'];
		
		$weather = $fix['weather_report'];
		if(is_array($weather)){
			$weather = json_encode($weather);
		}
		
		$commentaries = $fix['commentaries'];
		if(is_array($commentaries)){
			$commentaries = json_encode($commentaries);
		}
		
		$leg = $fix['leg'];
		if(is_array($leg)){
			$leg = json_encode($leg);
		}
		
		$colors = $fix['colors'];
		if(is_array($colors)){
			$colors = json_encode($colors);
		}

		if($time['status'] == 'FT'){
			insertTeamLastGameDate($db,$fix['localteam_id'],$time['starting_at']['date_time']);
			insertTeamLastGameDate($db,$fix['visitorteam_id'],$time['starting_at']['date_time']);
		}
		$insArray = array($fix['id'], $fix['league_id'], $fix['season_id'], $fix['stage_id'], $fix['round_id'], $fix['group_id'], $fix['aggregate_id'], $fix['venue_id'], $fix['referee_id'], $fix['localteam_id'], $fix['visitorteam_id'], $weather, $commentaries, $fix['attendance'], $fix['pitch'], $fix['winning_odds_calculated'], $formation['localteam_formation'], $formation['visitorteam_formation'], $scores['localteam_score'], $scores['visitorteam_score'], $scores['localteam_pen_score'], $scores['visitorteam_pen_score'], $scores['ht_score'], $scores['ft_score'], $scores['et_score'], $time['status'], $time['starting_at']['date_time'], $time['starting_at']['date'], $time['starting_at']['time'], $time['starting_at']['timestamp'], $time['starting_at']['timezone'], $time['minute'], $time['second'], $time['added_time'], $time['extra_minute'], $time['injury_time'], $coaches['localteam_coach_id'], $coaches['visitorteam_coach_id'], $standings['localteam_position'], $standings['visitorteam_position'], $leg, $colors, $fix['deleted'],$fix['id'], $fix['league_id'], $fix['season_id'], $fix['stage_id'], $fix['round_id'], $fix['group_id'], $fix['aggregate_id'], $fix['venue_id'], $fix['referee_id'], $fix['localteam_id'], $fix['visitorteam_id'], $weather, $commentaries, $fix['attendance'], $fix['pitch'], $fix['winning_odds_calculated'], $formation['localteam_formation'], $formation['visitorteam_formation'], $scores['localteam_score'], $scores['visitorteam_score'], $scores['localteam_pen_score'], $scores['visitorteam_pen_score'], $scores['ht_score'], $scores['ft_score'], $scores['et_score'], $time['status'], $time['starting_at']['date_time'], $time['starting_at']['date'], $time['starting_at']['time'], $time['starting_at']['timestamp'], $time['starting_at']['timezone'], $time['minute'], $time['second'], $time['added_time'], $time['extra_minute'], $time['injury_time'], $coaches['localteam_coach_id'], $coaches['visitorteam_coach_id'], $standings['localteam_position'], $standings['visitorteam_position'], $leg, $colors, $fix['deleted']);
		
		$insert->execute($insArray);
			}
	}
}

?>