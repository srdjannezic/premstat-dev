<?php
header("Access-Control-Allow-Origin: *");

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json;charset=utf-8');
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

require ABSPATH . 'config/database.php';
require 'functions.php';

$pdo = connection('open');
$type = '';
$limit = 20;
$search = 0;
$venue = false;
$value = '';

$type = isset($_GET['type']) ? $_GET['type'] : $type;
$venue = isset($_GET['venue']) ? $_GET['venue'] : $venue;
$value = isset($_GET['value']) ? $_GET['value'] : $value;
$limit = isset($_GET['limit']) ? $_GET['limit'] : $limit;
$search = isset($_GET['search']) ? $_GET['search'] : $search;

function getSingleMatch($pdo){

	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];

	$matchid = $_GET['id'];
	$limit = 6;
	$limit = isset($_GET['limit']) ? $_GET['limit'] : $limit;

	$stmt = $pdo->prepare("select * from sportmonks_fixtures where id = :id");
	$stmt->execute(array(':id'=>$matchid));
	//var_dump($matchid);
	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$export = array();
	foreach ($fetch as $row) {
		$home_id = $row['localteam_id'];
		$away_id = $row['visitorteam_id'];

		$hometeam = getTeam($pdo,$home_id)['name'];
		$awayteam = getTeam($pdo,$away_id)['name'];

		$hometeamlogo = getTeam($pdo,$home_id)['logo_path'];
		$awayteamlogo = getTeam($pdo,$away_id)['logo_path'];		
		
		$homeacro = getTeam($pdo,$home_id)['mapped_teamname'];
		$awayacro = getTeam($pdo,$away_id)['mapped_teamname'];

		$g1 = $row['localteam_score'];
		$g2 = $row['visitorteam_score'];
		$res = $g1 . '-' . $g2;
		$total = $g1 + $g2;
		$roi_home = getROI($pdo,$home_id,$season_id,'1x2',0)['roi'];
		$roi_away = getROI($pdo,$away_id,$season_id,'1x2',0)['roi'];

		$date = date('l d F',strtotime($row['date']));
		$time = date('H:i',strtotime($row['date_time']));

		$datetime = new DateTime($row['date_time']);

		$isodate = $datetime->format(DateTime::ATOM); // Updated ISO8601
		
		$homelast6 = getTeamLastX($pdo,$home_id,$limit,$season_id);
		$last6HomeArr = array();
		$last6ResHomeArr = array();
		foreach ($homelast6 as $value) {

			if($value['localteam_id'] == $home_id){
				$g1x = $value['localteam_score'];
				$g2x = $value['visitorteam_score'];
			}
			else{
				$g1x = $value['visitorteam_score'];
				$g2x = $value['localteam_score'];		
			}	
			$gtotal = $g1x + $g2x;

			$last6HomeArr['form'][] = getForm($g1x,$g2x);
			$last6HomeArr['btts'][] = isBTSYes($g1x,$g2x);
			$last6HomeArr['cs'][] = isCSYes($g2x);
			$last6HomeArr['gf'][] = isGoalsForOverX($g1x,$g2x,1.5);
			$last6HomeArr['ga'][] = isGoalsAgainstOverX($g1x,$g2x,1.5);
			$last6HomeArr['over'][] = isOverX($gtotal,2.5);

			$hteam = getTeam($pdo,$value['localteam_id'])['name'];
			$ateam = getTeam($pdo,$value['visitorteam_id'])['name'];
			
			$hteamlogo = getTeam($pdo,$value['localteam_id'])['logo_path'];
			$ateamlogo = getTeam($pdo,$value['visitorteam_id'])['logo_path'];

			$hacro = getTeam($pdo,$value['localteam_id'])['mapped_teamname'];
			$aacro = getTeam($pdo,$value['visitorteam_id'])['mapped_teamname'];

			$datetime2 = new DateTime($value['date_time']);

			$isodate2 = $datetime->format(DateTime::ATOM); // Updated ISO8601
			
			$last6ResHomeArr[] = array('hometeam'=>array('name'=>$hteam,'acronym'=>$hacro,'logo'=>$hteamlogo),'awayteam'=>array('name'=>$ateam,'acronym'=>$aacro,'logo'=>$ateamlogo),'datetime'=>$value['date_time'], 
				'isodate'=>$isodate2,'home_score' => $value['localteam_score'], 'away_score' => $value['visitorteam_score'] );
		}

		$awaylast6 = getTeamLastX($pdo,$away_id,$limit,$season_id);
		$last6AwayArr = array();
		$last6ResAwayArr = array();

		foreach ($awaylast6 as $value) {
			if($value['visitorteam_id'] == $home_id){
				$g1x = $value['localteam_score'];
				$g2x = $value['visitorteam_score'];
			}
			else{
				$g1x = $value['visitorteam_score'];
				$g2x = $value['localteam_score'];		
			}	
			$gtotal = $g1x + $g2x;

			$last6AwayArr['form'][] = getForm($g1x,$g2x);
			$last6AwayArr['btts'][] = isBTSYes($g1x,$g2x);
			$last6AwayArr['cs'][] = isCSYes($g2x);
			$last6AwayArr['gf'][] = isGoalsForOverX($g1x,$g2x,1.5);
			$last6AwayArr['ga'][] = isGoalsAgainstOverX($g1x,$g2x,1.5);
			$last6AwayArr['over'][] = isOverX($gtotal,2.5);

			$hteam = getTeam($pdo,$value['localteam_id'])['name'];
			$ateam = getTeam($pdo,$value['visitorteam_id'])['name'];

			$hteamlogo = getTeam($pdo,$value['localteam_id'])['logo_path'];
			$ateamlogo = getTeam($pdo,$value['visitorteam_id'])['logo_path'];
			
			$hacro = getTeam($pdo,$value['localteam_id'])['mapped_teamname'];
			$aacro = getTeam($pdo,$value['visitorteam_id'])['mapped_teamname'];

			$datetime2 = new DateTime($value['date_time']);

			$isodate2 = $datetime->format(DateTime::ATOM); // Updated ISO8601	

			$last6ResAwayArr[] = array('hometeam'=>array('name'=>$hteam,'acronym'=>$hacro,'logo'=>$hteamlogo),'awayteam'=>array('name'=>$ateam,'acronym'=>$aacro,'logo'=>$ateamlogo),'datetime'=>$value['date_time'],
			 'isodate'=>$isodate2,'home_score' => $value['localteam_score'], 'away_score' => $value['visitorteam_score'] );

		}
		//var_dump($row);
		$status = '';
		if($row['status'] == 'FT'){
			$status = "finished";
		}
		if($row['status'] == 'NS'){
			$status = "to_begin";
		}

		//var_dump($row);

		$export[] = array('match_status'=>$status,'half_time_score'=>array('home_score'=>0,'away_score'=>0),'game_location'=>'undefined','hometeam'=>array('teamId'=>$home_id,'name'=>$hometeam,'acronym'=>$homeacro,'roi'=>$roi_home,'stat'=>$last6HomeArr, 
		'last' => $last6ResHomeArr,'logo'=>$hometeamlogo),'awayteam'=>array('teamId'=>$away_id,'name'=>$awayteam,'acronym'=>$awayacro,'roi'=>$roi_away,
		'stat'=>$last6AwayArr,'last'=>$last6ResAwayArr,'logo'=>$awayteamlogo),'date'=>$date, 'time'=>$time, 'isodate'=>$isodate, 'result' => $res);

	}

	return $export;
}

function getSingleTeam($pdo) {
	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];

	$teamid = $_GET['id'];
	$limit = 6;
	$limit = isset($_GET['limit']) ? $_GET['limit'] : $limit;

	$stmt = $pdo->prepare("select * from sportmonks_teams where id = :id");
	$stmt->execute(array(':id'=>$teamid));

	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$export = array();
	$last6HomeArr = array();
 	
 	$roibttsyes = getROI($pdo,$teamid,$season_id,'btts-yes');
	$roibttsno = getROI($pdo,$teamid,$season_id,'btts-no');
	$roicsyes = getROI($pdo,$teamid,$season_id,'cs-yes');
	$roicsno = getROI($pdo,$teamid,$season_id,'cs-yes');
	$roiover = getROI($pdo,$teamid,$season_id,'over');
	$roiunder = getROI($pdo,$teamid,$season_id,'under');
	$roigfover = getROI($pdo,$teamid,$season_id,'gfover');
	$roigfunder = getROI($pdo,$teamid,$season_id,'gfunder');
	$roigaover = getROI($pdo,$teamid,$season_id,'gaover');
	$roigaunder = getROI($pdo,$teamid,$season_id,'gaunder');
	$roi1x2 = getROI($pdo,$teamid,$season_id,'1x2');
	$roiwtn = getROI($pdo,$teamid,$season_id,'wtn');

	foreach ($fetch as $row) {
		$id = $row['id'];
		$standings = getTeamStandings($pdo,$id);
		if(isset($standings[0])){
			$standings = $standings[0];
		}
		else{
			$standings['position'] = '';
		}
		//var_dump($standings);
		$teamname = $row['name'];
		$teamlogo = $row['logo_path'];

		$acronym = getTeam($pdo,$id)['mapped_teamname'];

		$teamlast6 = getTeamLastX($pdo,$id,$limit,$season_id);
		$last6Arr = array();

		$resultsArray = json_decode(getTeamRoi($pdo,$id,'1x2','overall',0.0)['last6']);

		$stmt = $pdo->prepare("select * from sportmonks_fixtures where league_id = 8 and (localteam_id = :team or visitorteam_id = :team) and season_id = :season and date_time > NOW() order by date_time");
		$stmt->execute(array(':season'=>$season_id,':team'=>$id));

		$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$fixtures = array();
		foreach ($fetch as $row) {
			$home_id = $row['localteam_id'];
			$away_id = $row['visitorteam_id'];

			$hometeam = getTeam($pdo,$home_id)['name'];
			$awayteam = getTeam($pdo,$away_id)['name'];
			
			$hometeamlogo = getTeam($pdo,$home_id)['logo_path'];
			$awayteamlogo = getTeam($pdo,$away_id)['logo_path'];

			$homeacro = getTeam($pdo,$home_id)['mapped_teamname'];
			$awayacro = getTeam($pdo,$away_id)['mapped_teamname'];

			$date = date('l d F',strtotime($row['date']));
			$time = date('H:i',strtotime($row['date_time']));
			$datetime = new DateTime($row['date_time']);
			$isodate = $datetime->format(DateTime::ATOM); // Updated ISO8601


			$l6Home = getTeamLastX($pdo,$home_id,6,$season_id);

			foreach ($l6Home as $value) {
				$last6HomeArr = getSimpleLast6Array($value,$pdo,false,false,'overall');
			}	

			$l6Away = getTeamLastX($pdo,$away_id,6,$season_id);

			foreach ($l6Home as $value) {
				$last6AwayArr = getSimpleLast6Array($value,$pdo,false,false,'overall');
			}				

			$fixtures[] = array('date'=>$date,'time'=>$time,'isodate'=>$isodate, 'matchid'=>$row['id'],'hometeam'=>array('id'=>$home_id,'name'=>$hometeam,'acronym'=>$homeacro,'last6'=>$last6HomeArr,'logo'=>$hometeamlogo),
			'awayteam'=>array('id'=>$away_id,'name'=>$awayteam,'acronym'=>$awayacro,'last6'=>$last6AwayArr,'logo'=>$awayteamlogo));

		}


		$export[] = 
		array(
			'team'=>array(
				'name'=>$teamname,
				'teamPosition'=>(int)$standings['position'],
				'logo'=>$teamlogo,
				'acronym'=>$acronym,
				'roistats'=>array(
				'btts-yes'=>$roibttsyes,
				'btts-no'=>$roibttsno,
				'cs-yes'=>$roicsyes,
				'cs-no'=>$roicsno,
				'over'=>$roiover,
				'under'=>$roiunder,
				'gfover'=>$roigfover,
				'gfunder'=>$roigfunder,
				'gaover'=>$roigaover,
				'gaunder'=>$roigaunder,
				'1x2'=>$roi1x2,
				'wtn'=>$roiwtn,
				),
				'roi6'=>getROI($pdo,$teamid,$season_id,'1x2',0.0,'overall',6)['roi'],
				'results'=>$resultsArray,
				'fixtures' => $fixtures
			)
		);

	}

	return $export;
}

function getJson($pdo,$id,$venue=false,$type=false,$search=0.00){
	$where = '';
	if($id == 'teamstats'){
		if(!isset($_GET['search'])){
			$stmt = $pdo->prepare("select * from baby where id = :id and venue = :venue and tip = :type");
			$stmt->execute(array(':id'=>$id,':venue'=>$venue,':type'=>$type));
		}
		else{
			$stmt = $pdo->prepare("select * from baby where id = :id and venue = :venue and tip = :type 
		and search = :search");
			$stmt->execute(array(':id'=>$id,':venue'=>$venue,':type'=>$type,':search'=>$search));
		}
		$json = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	elseif($id == 'wins') {
			$stmt = $pdo->prepare("select * from baby where id = :id and venue = :venue");
			$stmt->execute(array(':id'=>$id,':venue'=>$venue));	
			$json = $stmt->fetchAll(PDO::FETCH_ASSOC);	
	}
	else{
		$stmt = $pdo->prepare("select * from baby where id = :id " . $where);
		$stmt->execute(array(':id'=>$id));
		$json = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	return $json;	
}

if( isset($_GET['team']) ){

	echo json_encode(getSingleTeam($pdo),JSON_UNESCAPED_SLASHES);
	
}

if( isset($_GET['teamlist']) ){
	$json = getJson($pdo,'teamlist');
	if(isset($json[0])){
		echo $json[0]['json'];
	}
}

if( isset($_GET['teamstats']) ){
	$json = getJson($pdo,'teamstats',$venue,$type,$search);
	if(isset($json[0])){
		echo $json[0]['json'];
	}
}


if( isset($_GET['matches']) ){
	$json = getJson($pdo,'matches');
	if(isset($json[0])){
		echo $json[0]['json'];
	}
}

if( isset($_GET['match']) ){
	$json = getJson($pdo,'match');
	if(isset($json[0])){
		echo stripslashes(json_encode($json[0]['json'],JSON_UNESCAPED_SLASHES));
	}
	else{
		echo json_encode(getSingleMatch($pdo),JSON_UNESCAPED_SLASHES);
	}
}

if( isset($_GET['wins']) || isset($_GET['league'])){
	$json = getJson($pdo,'wins',$venue);
	if(isset($json[0])){
		echo $json[0]['json'];
	}
}