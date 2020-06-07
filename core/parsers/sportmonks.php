<?php
require ABSPATH . 'core/helpers.php';


function parseSportMonks($api,$name,$from='',$to='',$season_id='', $team_id=''){
	$url = "https://soccer.sportmonks.com/api/v2.0/{$name}?api_token=" . $api . '&tz=Europe/London';

	if($name == 'fixtures'){
		$url = "https://soccer.sportmonks.com/api/v2.0/{$name}/between/{$from}/{$to}/{$team_id}?api_token=" . $api . '&tz=Europe/London';
	}

	if($name == 'livescores/now'){
		$url = "https://soccer.sportmonks.com/api/v2.0/livescores/now?api_token=" . $api . '&include=localTeam,visitorTeam,substitutions,goals,cards,other,corners,lineup,bench,sidelined,stats,comments,tvstations,highlights,league,season,round,stage,referee,events,venue,odds,flatOdds,inplay,localCoach,visitorCoach,group,trends&tz=Europe/London';
//var_dump($url);
	}
	
	if($name == 'teams' || $name == 'rounds' || $name == 'stages' || $name == 'standings'){
		$url = "https://soccer.sportmonks.com/api/v2.0/{$name}/season/{$season_id}?api_token=" . $api . '&tz=Europe/London';
	}
	
	if($name == 'test'){
		$url = 'http://premstat.tk/crons/test.json';
	}

	//var_dump($url);
	$json = getURL($url);
    //echo $json;
	$decoded = json_decode($json,TRUE);
	//var_dump($decoded);
	$fordb = $decoded['data'];

	return $fordb;
}


?>