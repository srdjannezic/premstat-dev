<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
date_default_timezone_set('Europe/London');
require ABSPATH . 'config/database.php';
require 'functions.php';


$pdo = connection('open');


function getMatches($pdo){ 
	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];
	$search = 0;

	//$search = $_GET['search'];
	$export = array();
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where league_id = 8 and season_id = :season and date_time > NOW() order by date_time DESC");
	$stmt->execute(array(':season'=>$season_id));

	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
		////var_dump($hometeam . " " . $awayteam);
		//$homelast6 = getTeamLastX($pdo,$home_id,6,$season_id);
		$last6HomeArr = getTeamRoi($pdo,$home_id,'1x2','overall',0.0)['last6'];
		$last6AwayArr = getTeamRoi($pdo,$away_id,'1x2','overall',0.0)['last6'];

		////var_dump($last6HomeArr);

		$export[] = array('date'=>$date,'time'=>$time,'isodate'=>$isodate,'matchid'=>$row['id'],'hometeam'=>array('id'=>$home_id,'name'=>$hometeam,'acronym'=>$homeacro,'last6'=>$last6HomeArr,'logo'=>$hometeamlogo),
		'awayteam'=>array('id'=>$away_id,'name'=>$awayteam,'acronym'=>$awayacro,'last6'=>$last6AwayArr,'logo'=>$awayteamlogo));

	}

	$export2 = array();
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where league_id = 8 and season_id = :season and status = 'FT' order by date_time");
	$stmt->execute(array(':season'=>$season_id));

	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($fetch as $row) {
		$home_id = $row['localteam_id'];
		$away_id = $row['visitorteam_id'];
		
		$hometeamlogo = getTeam($pdo,$home_id)['logo_path'];
		$awayteamlogo = getTeam($pdo,$away_id)['logo_path'];
		
		$hometeam = getTeam($pdo,$home_id)['name'];
		$awayteam = getTeam($pdo,$away_id)['name'];

		$homeacro = getTeam($pdo,$home_id)['mapped_teamname'];
		$awayacro = getTeam($pdo,$away_id)['mapped_teamname'];

		$g1 = $row['localteam_score'];
		$g2 = $row['visitorteam_score'];
		$res = $g1 . ' - ' . $g2;
		$total = $g1 + $g2;

		$date = date('l d F',strtotime($row['date']));
		$time = date('H:i',strtotime($row['date_time']));
		$datetime2 = new DateTime($row['date_time']);
		$isodate2 = $datetime2->format(DateTime::ATOM); // Updated ISO8601		
		
		$bts = isBTSYes($g1,$g2);
		$csH = isCSYes($g2);
		$csA = isCSYes($g1);
		$gfH = isGoalsForOverX($g1,$g2,1.5);
		$gfA = isGoalsForOverX($g2,$g1,1.5);
		$gaH = isGoalsAgainstOverX($g1,$g2,1.5);
		$gaA = isGoalsAgainstOverX($g2,$g1,1.5);
		$o25 = isOverX($total,2.5);
		
		$last6HomeArr = getTeamRoi($pdo,$home_id,'1x2','overall',0.0)['last6'];
		$last6AwayArr = getTeamRoi($pdo,$away_id,'1x2','overall',0.0)['last6'];
		

		////var_dump($last6HomeArr);
		$export2[] = array('home_score' => $row['localteam_score'], 'away_score' => $row['visitorteam_score'], 'date'=>$date, 
			'time'=>$time, 'isodate'=>$isodate2, 'result' => $res, 'matchid'=>$row['id'],
			'hometeam'=>array('id'=>$row['localteam_id'],'name'=>$hometeam,'acronym'=>$homeacro, 'logo'=>$hometeamlogo,'last6'=>$last6HomeArr,'stat'=>array('win'=>isWin($row['localteam_score'],$row['visitorteam_score']),'draw'=>isDraw($row['localteam_score'],$row['visitorteam_score']),'loss'=>isLoss($row['localteam_score'],$row['visitorteam_score']),'R'=>getForm($g1,$g2),'BTTS'=>$bts,'CS'=>$csH,'Over'=>$o25,'GF'=>$gfH,'GA'=>$gaH)),
			'awayteam'=>array('id'=>$row['visitorteam_id'],'name'=>$awayteam,'acronym'=>$awayacro, 'logo'=>$awayteamlogo,'last6'=>$last6AwayArr,'stat'=>array('win'=>isWin($row['visitorteam_score'],$row['localteam_score']),'draw'=>isDraw($row['visitorteam_score'],$row['localteam_score']),'loss'=>isLoss($row['visitorteam_score'],$row['localteam_score']),'R'=>getForm($g2,$g1),'BTTS'=>$bts,'CS'=>$csA,'Over'=>$o25,'GF'=>$gfA,'GA'=>$gaA)) 
		);

	}
	//htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
	$export = json_encode(array('fixtures'=>$export,'results'=>$export2));
	//echo $export;
	$prepare = $pdo->prepare('INSERT INTO baby(id,tip,venue,json) 
	VALUES(:id,:tip,:venue,:json) 
	ON DUPLICATE KEY UPDATE
	json = :json');
	//echo $export;
	$state = $prepare->execute(array(':id'=>'matches',':tip'=>'0',':venue'=>'overall',':json'=>$export));
	////var_dump($state);
	return array('fixtures'=>$export,'results'=>$export2);
}


function getAllTeams($pdo){
	$export = array();
	$season = getCurrentSeason($pdo);
	$season_id = $season['id'];

	$stmt = $pdo->prepare("select * from sportmonks_teams where country_id = 462 group by name");
	$stmt->execute();

	$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($fetch as $team) {
$standings = getTeamStandings($pdo,$team['id']);
////var_dump($standings[0]);
		$acronym = getTeam($pdo,$team['id'])['mapped_teamname'];
		$stmt2 = $pdo->prepare("select * from sportmonks_fixtures where league_id = 8 and season_id = :season and (localteam_id = :team or visitorteam_id = :team) group by localteam_id, visitorteam_id order by date_time DESC limit 1");
		$stmt2->execute(array(':team'=>$team['id'],':season'=>$season_id));
		$fetch2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

		////var_dump($fetch2);

		foreach ($fetch2 as $value) {
			$export[] = array('name'=>$team['name'],'acronym'=>$acronym,'id'=>$team['id'],'logo'=>$team['logo_path']);
		}
		

	}

	$export = json_encode($export);
	$prepare = $pdo->prepare('INSERT INTO baby(id,tip,venue,json) 
	VALUES(:id,:tip,:venue,:json) 
	ON DUPLICATE KEY UPDATE
	json = :json');
	$prepare->execute(array(':id'=>'teamlist',':tip'=>'',':venue'=>'',':json'=>$export));

	return $export;
}



getMatches($pdo);
getAllTeams($pdo);

ob_end_flush();
?>