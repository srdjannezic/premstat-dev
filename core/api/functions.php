<?php
//tg = total goals
function isOverX($tg,$x){
	if($tg >= $x){
		return true;
	}
	else{
		return false;
	}
}

function isUnderX($tg,$x){
	if($tg <= $x){
		return true;
	}
	else{
		return false;
	}
}

function isBTSYes($g1,$g2){
	if($g1 > 0 and $g2 > 0){
		return true;
	}
	else{
		return false;
	}
}

function isBTSNo($g1,$g2){
	if($g1 == 0 or $g2 == 0){
		return true;
	}
	else{
		return false;
	}
}

function isCSYes($g2){
	if($g2 == 0){
		return true;
	}
	else{
		return false;
	}
}

function isCSNo($g2){
	if($g2 > 0){
		return true;
	}
	else{
		return false;
	}
}

function isWTN($g1,$g2){
	if($g1 > $g2 and $g2 == 0){
		return true;
	}
	else{
		return false;
	}
}

function isGoalsForOverX($g1,$g2,$scope){
	if($g1 > $g2 and $g1 >= $scope){
		return true;
	}
	else{
		return false;
	}
}

function isGoalsForUnderX($g1,$g2,$scope){
	if($g1 > $g2 and $g1 <= $scope){
		return true;
	}
	else{
		return false;
	}
}

function isGoalsAgainstOverX($g1,$g2,$scope){
	if($g2 > $g1 and $g2 >= $scope){
		return true;
	}
	else{
		return false;
	}
}

function isGoalsAgainstUnderX($g1,$g2,$scope){
	if($g2 > $g1 and $g2 <= $scope){
		return true;
	}
	else{
		return false;
	}
}

function isWin($g1,$g2){
	if($g1 > $g2){
		return true;
	}
	elseif($g1 == $g2){
		return 'void';
	}
	else{
		return false;
	}
}

function isDraw($g1,$g2){
	if($g1 == $g2){
		return true;
	}
	else{
		return false;
	}
}

function isLoss($g1,$g2){
	if($g1 < $g2){
		return true;
	}
	elseif($g1 == $g2){
		return 'void';
	}
	else{
		return false;
	}
}

function getForm($g1,$g2){
	if($g1 > $g2){
		return 'win';
	}
	elseif($g1 == $g2){
		return 'draw';
	}
	else{
		return 'loss';
	}
}

function isNewGamesAvailable(){
	
}

function translateType($type){
	if($type == 'over') $type = 'Total Goals';
	if($type == 'under') $type = 'Total Goals';
	if($type == 'btts-yes') $type = 'Both Teams To Score';
	if($type == 'cs-yes') $type = 'Clean Sheet';
	if($type == 'btts-no') $type = 'Both Teams To Score';
	if($type == 'cs-no') $type = 'Clean Sheet';
	if($type == '1x2' or $type == '1x2-draws' or $type == '1x2-loss') $type = 'Full Time Result';

	if($type == 'wtn') $type = 'To Win to Nil';
	if($type == 'gfover') $type = 'Team Total Goals';
	if($type == 'gfunder') $type = 'Team Total Goals';
	if($type == 'gaover') $type = 'Team Total Goals';
	if($type == 'gaunder') $type = 'Team Total Goals';
	return $type;
}

function getTeamStandings($pdo,$teamid){
$stmt = $pdo->prepare('select * from sportmonks_standings where team_id = :id');
$stmt->execute(array(':id'=>$teamid));
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLast6Array($value,$pdo,$type=false,$search=false, $venue='overall'){
	$g1 = $value['localteam_score'];
	$g2 = $value['visitorteam_score'];
	
	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];	

	$hteam = getTeam($pdo,$value['localteam_id']);
	$homename = $hteam['name'];
	
	$homeacro = $hteam['mapped_teamname'];
	$opteam = getTeam($pdo,$value['visitorteam_id']);
	$awayname = $opteam['name'];
	$awayacro = $opteam['mapped_teamname'];

	$homestands = json_decode(getTeamStandings($pdo,$value['localteam_id'])[0]['overall']);
	$awaystands = json_decode(getTeamStandings($pdo,$value['localteam_id'])[0]['overall']);


	
	$total = $g1 + $g2;

	$bts = isBTSYes($g1,$g2);
	$csH = isCSYes($g2); 
	$csA = isCSYes($g1);
	$gfH = isGoalsForOverX($g1,$g2,1.5);
	$gfA = isGoalsForOverX($g2,$g1,1.5);
	$gaH = isGoalsAgainstOverX($g1,$g2,1.5);
	$gaA = isGoalsAgainstOverX($g2,$g1,1.5);
	$o25 = isOverX($total,2.5);		

 	$roibttsyes_h = getROI($pdo,$value['localteam_id'],$season_id,'btts-yes',0,$venue,6);
	$roibttsno_h = getROI($pdo,$value['localteam_id'],$season_id,'btts-no',0,$venue,6);
	$roicsyes_h = getROI($pdo,$value['localteam_id'],$season_id,'cs-yes',0,$venue,6);
	$roicsno_h = getROI($pdo,$value['localteam_id'],$season_id,'cs-yes',0,$venue,6);
	$roiover_h = getROI($pdo,$value['localteam_id'],$season_id,'over',$search,$venue,6);
	$roiunder_h = getROI($pdo,$value['localteam_id'],$season_id,'under',$search,$venue,6);
	$roigfover_h = getROI($pdo,$value['localteam_id'],$season_id,'gfover',$search,$venue,6);
	$roigfunder_h = getROI($pdo,$value['localteam_id'],$season_id,'gfunder',$search,$venue,6);
	$roigaover_h = getROI($pdo,$value['localteam_id'],$season_id,'gaover',$search,$venue,6);
	$roigaunder_h = getROI($pdo,$value['localteam_id'],$season_id,'gaunder',$search,$venue,6);
	$roi1x2_h = getROI($pdo,$value['localteam_id'],$season_id,'1x2',$search,$venue,6);
	$roiwtn_h = getROI($pdo,$value['localteam_id'],$season_id,'wtn',$search,$venue,6);

	$roibttsyes_a = getROI($pdo,$value['visitorteam_id'],$season_id,'btts-yes',0,$venue,6);
	$roibttsno_a = getROI($pdo,$value['visitorteam_id'],$season_id,'btts-no',0,$venue,6);
	$roicsyes_a = getROI($pdo,$value['visitorteam_id'],$season_id,'cs-yes',0,$venue,6);
	$roicsno_a = getROI($pdo,$value['visitorteam_id'],$season_id,'cs-yes',0,$venue,6);
	$roiover_a = getROI($pdo,$value['visitorteam_id'],$season_id,'over',$search,$venue,6);
	$roiunder_a = getROI($pdo,$value['visitorteam_id'],$season_id,'under',$search,$venue,6);
	$roigfover_a = getROI($pdo,$value['visitorteam_id'],$season_id,'gfover',$search,$venue,6);
	$roigfunder_a = getROI($pdo,$value['visitorteam_id'],$season_id,'gfunder',$search,$venue,6);
	$roigaover_a = getROI($pdo,$value['visitorteam_id'],$season_id,'gaover',$search,$venue,6);
	$roigaunder_a = getROI($pdo,$value['visitorteam_id'],$season_id,'gaunder',$search,$venue,6);
	$roi1x2_a = getROI($pdo,$value['visitorteam_id'],$season_id,'1x2',$search,$venue,6);
	$roiwtn_a = getROI($pdo,$value['visitorteam_id'],$season_id,'wtn',$search,$venue,6);

	$hteamlogo = getTeam($pdo,$value['localteam_id'])['logo_path'];
	$ateamlogo = getTeam($pdo,$value['visitorteam_id'])['logo_path'];
			
	$date = date('l d F',strtotime($value['date']));
	$time = date('H:i',strtotime($value['date_time']));
	$datetime = new DateTime($value['date_time']);
	$isodate = $datetime->format(DateTime::ATOM); // Updated ISO860

	$last6Arr = array(
		'matchid'=>$value['id'],

		'hometeam'=>array('id'=>$value['localteam_id'],'name'=>$homename,'acronym'=>$homeacro, 
			 'roi6'=>array(
				'btts-yes'=>$roibttsyes_h,
				'btts-no'=>$roibttsno_h,
				'cs-yes'=>$roicsyes_h,
				'cs-no'=>$roicsno_h,
				'over'=>$roiover_h,
				'under'=>$roiunder_h,
				'gfover'=>$roigfover_h,
				'gfunder'=>$roigfunder_h,
				'gaover'=>$roigaover_h,
				'gaunder'=>$roigaunder_h,
				'1x2'=>$roi1x2_h,
				'wtn'=>$roiwtn_h,
			), 'logo'=>$hteamlogo, 'win'=>isWin($value['localteam_score'],$value['visitorteam_score']),
		'draw'=>isDraw($value['localteam_score'],$value['visitorteam_score']),
		'loss'=>isLoss($value['localteam_score'],$value['visitorteam_score']),
		'R'=>getForm($g1,$g2),'BTTS'=>$bts,'CS'=>$csH,'Over'=>$o25,'GF'=>$gfH,'GA'=>$gaH,'numbers'=>$homestands),
		'awayteam'=>array('id'=>$value['visitorteam_id'],'name'=>$awayname,'acronym'=>$awayacro, 
			 'roi6'=>array(
				'btts-yes'=>$roibttsyes_a,
				'btts-no'=>$roibttsno_a,
				'cs-yes'=>$roicsyes_a,
				'cs-no'=>$roicsno_a,
				'over'=>$roiover_a,
				'under'=>$roiunder_a,
				'gfover'=>$roigfover_a,
				'gfunder'=>$roigfunder_a,
				'gaover'=>$roigaover_a,
				'gaunder'=>$roigaunder_a,
				'1x2'=>$roi1x2_a,
				'wtn'=>$roiwtn_a,
			),
			'logo'=>$ateamlogo, 'win'=>isWin($value['visitorteam_score'],$value['localteam_score']),
		'draw'=>isDraw($value['visitorteam_score'],$value['localteam_score']),
		'loss'=>isLoss($value['visitorteam_score'],$value['localteam_score']),
		'R'=>getForm($g2,$g1),'BTTS'=>$bts,'CS'=>$csA,'Over'=>$o25,'GF'=>$gfA,'GA'=>$gaA,'numbers'=>$awaystands),
		'home_score'=>$value['localteam_score'],
		'away_score'=>$value['visitorteam_score'],
		'isodate'=>$isodate);


	
	return $last6Arr;
}



function getROI($pdo,$team_id,$season_id,$type,$search=2.5,$venue='overall',$limit=false){
	$score_counter = 0;
	$roi = 0;
	//var_dump("SEARCHHHH: " . $search);
	if($venue == 'home'){
		if($limit)
			$score = getTeamLastXAtHome($pdo,$team_id,$limit,$season_id);
		else
			$score = getTeamScoreAtHome($pdo,$team_id,$season_id);
	}
	if($venue == 'away'){
		if($limit)
			$score = getTeamLastXAtAway($pdo,$team_id,$limit,$season_id);
		else
			$score = getTeamScoreAtAway($pdo,$team_id,$season_id);
	}
	if($venue == 'overall'){ 
		if($limit)
			$score = getTeamLastX($pdo,$team_id,$limit,$season_id);
		else
			$score = getTeamScore($pdo,$team_id,$season_id);
	}


	

	$myacro = "";

	foreach ($score as $value) {
		$myteam = getTeam($pdo,$value['localteam_id']);
		$rname = $myteam['name'];
		$myacro = $myteam['mapped_teamname'];

		$opteam = getTeam($pdo,$value['visitorteam_id']);
		$opname = $opteam['name'];
		$opacro = $opteam['mapped_teamname'];

		if($team_id == $value['localteam_id']){ //tim domacin
			$mygoals = $value['localteam_score'];
			$opgoals = $value['visitorteam_score'];
		}

		if($team_id == $value['visitorteam_id']){ //tim je gost
			$mygoals = $value['visitorteam_score'];
			$opgoals = $value['localteam_score'];
		} 

		//echo "<br/>";
		$lasttotal = $mygoals + $opgoals;
		//var_dump($lasttotal);
		$score_perc = false;
		if($type == 'over') $score_perc = isOverX($lasttotal,$search);
		if($type == 'under') $score_perc = isUnderX($lasttotal,$search);
		if($type == 'btts-yes') $score_perc = isBTSYes($mygoals,$opgoals);
		if($type == 'cs-yes') $score_perc = isCSYes($opgoals);	
		if($type == 'btts-no') $score_perc = isBTSNo($mygoals,$opgoals);
		if($type == 'cs-no') $score_perc = isCSNo($opgoals);	
		if($type == '1x2') $score_perc = isWin($mygoals,$opgoals);
		if($type == '1x2-draws') $score_perc = isDraw($mygoals,$opgoals);
		if($type == '1x2-loss') $score_perc = isLoss($mygoals,$opgoals);
		if($type == 'wtn') $score_perc = isWTN($mygoals,$opgoals);
		if($type == 'gfover') $score_perc = isGoalsForOverX($mygoals,$opgoals,$search);
		if($type == 'gfunder') $score_perc = isGoalsForUnderX($mygoals,$opgoals,$search);
		if($type == 'gaover') $score_perc = isGoalsAgainstOverX($mygoals,$opgoals,$search);
		if($type == 'gaunder') $score_perc = isGoalsAgainstUnderX($mygoals,$opgoals,$search);

		$odds = "";				
		$bet365type = translateType($type);
		if($team_id == $value['visitorteam_id']){ //tim je gost

			if($type == 'over' || $type == 'gaover' || $type == 'gfover'){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Away_EU'];
				if($odds === NULL) $odds = 1.00;
			}


			if($type == 'under' || $type == 'gaunder' || $type == 'gfunder'){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Home_EU'];
				if($odds === NULL) $odds = 1.00;
			}
		}
		else{ //tim je domacin
			if($type == 'over' || $type == 'gaover' || $type == 'gfover'){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Away_EU'];
				if($odds === NULL) $odds = 1.00;
			}


			if($type == 'under' || $type == 'gaunder' || $type == 'gfunder'){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Home_EU'];
				if($odds === NULL) $odds = 1.00;
			}
		}
		if($type == 'cs-yes' || $type == 'btts-yes' || $type == 'wtn'){
			$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Home_EU'];
		}

		if($type == 'cs-no' || $type == 'btts-no'){
			$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Away_EU'];
		}
		
		if($type == '1x2'){
			if(isDraw($mygoals,$opgoals)){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'],$season_id)['Draw_EU'];
			}
			else{
				if($team_id == $value['localteam_id']){
					$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Home_EU'];
				}
				else{
					$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Away_EU'];
				}
			}
		}

		if($type == '1x2-draws'){
			if(isDraw($mygoals,$opgoals)){
				$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'],$season_id)['Draw_EU'];
			}
		}

		if($type == '1x2-loss'){
			if(isLoss($mygoals,$opgoals)){
				if($team_id == $value['localteam_id']){
					$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Away_EU'];
				}
				else{
					$odds = getBet365Match($pdo,$myacro,$opacro,$bet365type,$value['date'])['Home_EU'];
				}
			}
		}


		if($odds > 0){
			if($score_perc === true){ 
				$roi = $roi + ($odds - 1);
				$score_counter ++;
			}
			else{
				$roi = $roi - 1;
			}
		}
		else{
			if($score_perc) $score_counter ++;
			$roi = $roi - 1;
		}
		//var_dump($myacro . " " . $opacro . " | " . " kvota:" . $odds . " ROI: " . );

		// if($team_id == 8){

		// 	//var_dump($type);
		// 	//var_dump(array($opacro,$acronym,$bet365type,$value['date']));
		 	//var_dump("TIP: " . $type . " VENUE: " . $venue . "DOMACIN ID: " . $value['localteam_id'] . ", ACRO: " . $myacro . " |  GOST ID: " . $value['visitorteam_id'] . ", ACRO: " . $opacro . "| KVOTA: " . $odds . " | ROI: " . $roi . ' Success: ' . $score_perc . " DATE: " . $value['date'] . " SEARCH: " . $search);
		//echo "<br/>";

		// }
	}
	return array('roi'=>round($roi,2),'score_counter'=>$score_counter);
}

function getSimpleLast6Array($value,$pdo,$type=false,$search=false, $venue='overall'){
	$g1 = $value['localteam_score'];
	$g2 = $value['visitorteam_score'];
	
	$season = getCurrentSeason($pdo);
	$season_name = $season['name'];
	$season_id = $season['id'];	

	$hteam = getTeam($pdo,$value['localteam_id']);
	$homename = $hteam['name'];
	
	$homeacro = $hteam['mapped_teamname'];
	$opteam = getTeam($pdo,$value['visitorteam_id']);
	$awayname = $opteam['name'];
	$awayacro = $opteam['mapped_teamname'];

	$homestands = json_decode(getTeamStandings($pdo,$value['localteam_id'])[0]['overall']);
	$awaystands = json_decode(getTeamStandings($pdo,$value['localteam_id'])[0]['overall']);


	
	$total = $g1 + $g2;

	$bts = isBTSYes($g1,$g2);
	$csH = isCSYes($g2); 
	$csA = isCSYes($g1);
	$gfH = isGoalsForOverX($g1,$g2,1.5);
	$gfA = isGoalsForOverX($g2,$g1,1.5);
	$gaH = isGoalsAgainstOverX($g1,$g2,1.5);
	$gaA = isGoalsAgainstOverX($g2,$g1,1.5);
	$o25 = isOverX($total,2.5);		

	$hteamlogo = getTeam($pdo,$value['localteam_id'])['logo_path'];
	$ateamlogo = getTeam($pdo,$value['visitorteam_id'])['logo_path'];
			
	$date = date('l d F',strtotime($value['date']));
	$time = date('H:i',strtotime($value['date_time']));
	$datetime = new DateTime($value['date_time']);
	$isodate = $datetime->format(DateTime::ATOM); // Updated ISO860

	$last6Arr = array(
		'matchid'=>$value['id'],

		'hometeam'=>array('id'=>$value['localteam_id'],'name'=>$homename,'acronym'=>$homeacro, 'win'=>isWin($value['localteam_score'],$value['visitorteam_score']),
		'draw'=>isDraw($value['localteam_score'],$value['visitorteam_score']),
		'loss'=>isLoss($value['localteam_score'],$value['visitorteam_score']),
		'R'=>getForm($g1,$g2),'BTTS'=>$bts,'CS'=>$csH,'Over'=>$o25,'GF'=>$gfH,'GA'=>$gaH,'numbers'=>$homestands),
		'awayteam'=>array('id'=>$value['visitorteam_id'],'name'=>$awayname,'acronym'=>$awayacro, 'logo'=>$ateamlogo, 'win'=>isWin($value['visitorteam_score'],$value['localteam_score']),
		'draw'=>isDraw($value['visitorteam_score'],$value['localteam_score']),
		'loss'=>isLoss($value['visitorteam_score'],$value['localteam_score']),
		'R'=>getForm($g2,$g1),'BTTS'=>$bts,'CS'=>$csA,'Over'=>$o25,'GF'=>$gfA,'GA'=>$gaA,'numbers'=>$awaystands),
		'home_score'=>$value['localteam_score'],
		'away_score'=>$value['visitorteam_score'],
		'isodate'=>$isodate);
	
	return $last6Arr;
}


function getTeam($pdo,$id){
	$stmt = $pdo->prepare("select name,short_code,mapped_teamname,logo_path from sportmonks_teams where id = :id limit 1");
	$stmt->execute(array(':id'=>$id));
	$team = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$return = isset($team[0]) ? $team[0] : null;
	return $return;
}

function getNextMatch($pdo,$team_id,$date){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where (localteam_id = :id or visitorteam_id = :id) and date >= NOW() limit 1");
	$stmt->execute(array(':id'=>$team_id,':date'=>$date));
	$match = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$return = isset($match[0]) ? $match[0] : null;
	return $return;
}

function getTeams($pdo){
	$stmt = $pdo->prepare("select name,short_code,mapped_teamname,logo_path from sportmonks_teams where country_id = 462");
	$stmt->execute();
	$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $teams;
}

function getBet365Match($pdo,$t1,$t2,$type,$date){
	$stmt = $pdo->prepare("select * from bet365_historic where (mapped_teamname1 = :t1 AND mapped_teamname2 = :t2) AND BetName = :type AND DATE(DateT) = DATE(:date)  ORDER BY `DateT` LIMIT 1");
	$stmt->execute(array(':t1'=>$t1,':t2'=>$t2,':type'=>$type,':date'=>$date));
	$odd = $stmt->fetchAll(PDO::FETCH_ASSOC);
	//var_dump($odd);
	if(isset($odd[0])){
		return $odd[0];
	}
	else
	return 0;
}

function getCurrentSeason($pdo){
	$stmt = $pdo->prepare("select season_id as id from sportmonks_fixtures where league_id = 8 and status = 'FT' order by date desc limit 1");
	$stmt->execute();
	$ses = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
	
	$stmt2 = $pdo->prepare("select * from sportmonks_seasons where id = :id limit 1");
	$stmt2->execute(array(':id'=>$ses['id']));
	$ses2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
	
	return $ses2[0];	
}

function getTotalRounds($pdo,$season){
	$stmt = $pdo->prepare("select name from sportmonks_rounds where season_id = :season and league_id = 8 order by name desc limit 1");
	$stmt->execute(array(':season'=>$season));
	$rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $rounds[0];		
}

function getTeamMP($pdo,$season_id, $id,$venue = 'overall'){
	$stmt = $pdo->prepare("select count(id) as mp from sportmonks_fixtures where (localteam_id = :id OR visitorteam_id = :id) and (league_id = 8 and status = 'FT' and season_id = :season) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season'=>$season_id));
	$mp = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $mp[0];	
}

function getTeamMPHome($pdo,$season_id, $id,$venue = 'overall'){
	$stmt = $pdo->prepare("select count(id) as mp from sportmonks_fixtures where localteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season'=>$season_id));
	$mp = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $mp[0];	
}

function getTeamMPAway($pdo,$season_id, $id,$venue = 'overall'){
	$stmt = $pdo->prepare("select count(id) as mp from sportmonks_fixtures where visitorteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season'=>$season_id));
	$mp = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $mp[0];	
}

function getTeamGoals($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where (localteam_id = :id or visitorteam_id = :id) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamGoalsHome($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where localteam_id = :id and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamGoalsAway($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where visitorteam_id = :id and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamWins($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		((localteam_id = :id and localteam_score > visitorteam_score) or
		(visitorteam_id = :id and localteam_score < visitorteam_score)) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}


function getTeamWinsHome($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(localteam_id = :id and localteam_score > visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamWinsAway($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(visitorteam_id = :id and localteam_score < visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamDraws($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(localteam_id = :id or visitorteam_id = :id) and localteam_score = visitorteam_score and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamDrawsHome($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(localteam_id = :id and localteam_score = visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamDrawsAway($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(visitorteam_id = :id and localteam_score = visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamLosses($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		((localteam_id = :id and localteam_score < visitorteam_score) or
		(visitorteam_id = :id and localteam_score > visitorteam_score)) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamLossesHome($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(localteam_id = :id and localteam_score < visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamLossesAway($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where 
		(visitorteam_id = :id and localteam_score > visitorteam_score) and 
		(league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamLastXAtHome($pdo,$id,$limit=6,$season_id=false){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where localteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time  DESC limit ".$limit);
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$last6 = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $last6;
}

function getTeamLastXAtAway($pdo,$id,$limit=6,$season_id=false){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where visitorteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC limit ".$limit);
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$last6 = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $last6;
}

function getTeamLastX($pdo,$id,$limit=6,$season_id=false){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where (localteam_id = :id OR visitorteam_id = :id) and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC limit ".$limit);
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$last6 = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $last6;
} 

function getTeamRoi($pdo,$id,$tip,$venue,$search){
	$stmt = $pdo->prepare("select * from teamstats where teamid = :id and tip = :tip and venue = :venue and search = :search limit 1");
	$stmt->execute(array(':id'=>$id,':tip'=>$tip,'venue'=>$venue,':search'=>$search));
	$roi = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!isset($roi[0])) $roi[0] = null;
	return $roi[0];
} 

function getTeamScore($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where (localteam_id = :id OR visitorteam_id = :id) and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamScoreAtHome($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where localteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

function getTeamScoreAtAway($pdo,$id,$season_id){
	$stmt = $pdo->prepare("select * from sportmonks_fixtures where visitorteam_id = :id and (league_id = 8 and status = 'FT' and season_id = :season_id) order by date_time DESC");
	$stmt->execute(array(':id'=>$id,':season_id'=>$season_id));
	$score = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $score;
}

?>