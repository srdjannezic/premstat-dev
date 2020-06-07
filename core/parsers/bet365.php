<?php
require ABSPATH . 'core/helpers.php';

/*
Full Time Result	bet365fulltime.xml *
Half Time Result	bet365halftime.xml *
Total Goals		bet365ou.xml *
Alternative Total Goals	bet365ou-ext.xml *
First Half Goals	bet365ou-1h.xml *
2nd Half Goals	bet365ou-2h.xml *
Team Total Goals	bet365ttgoals.xml *
To Score in Both Halves	bet365sbh.xml *
Both Teams to Score	bet365btts.xml *
Clean Sheet		bet365cs.xml *
To Win To Nil		bet365wintonil.xml *
Asian Handicap	bet365ah.xml *
Alternative Asian Handicap	bet365ah-ext.xml *
Asian Total Corners	bet365atcorners.xml *
Asian Total Cards	bet365atcards.xml *
Correct Score	bet365score.xml -
Half Time Correct Score	bet365score-1h.xml -
*/

function parse1x2($db){
////clearTable('Full Time Result',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365fulltime.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
$cronupdated = ""; $upddate = "";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == "Home Win") {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Draw") {
					$DrawOddsName = "Draw";
					$DrawOddsEU = $participant->attributes()->OddsDecimal;
					$DrawOddsUK = $participant->attributes()->Odds;
					$DrawOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Away Win") {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $DrawOddsName , " " , $DrawOddsEU , " " , "<font size='1'>" , $DrawOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		
		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		


		echo '<BR/>';
	} // foreach $event
	}
}
}


function parse1x2half($db){
//clearTable('Half-Time',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365halftime.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id=""; $betName = "Half-Time";$upddate="";$cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market

			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam) {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Draw") {
					$DrawOddsName = "Draw";
					$DrawOddsEU = $participant->attributes()->OddsDecimal;
					$DrawOddsUK = $participant->attributes()->Odds;
					$DrawOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == $awayTeam) {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $DrawOddsName , " " , $DrawOddsEU , " " , "<font size='1'>" , $DrawOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT,  FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?,  FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betName , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betName , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		
		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betName , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betName , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $DrawOddsEU , $DrawOddsUK , $DrawOddsID , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseOU($db){
//clearTable('Total Goals',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ou.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
	$betScope = NULL; $upddate=""; $cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$betScope = $participant->attributes()->Handicap;
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == "Under") {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Over") {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID,NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		
		$SQL2 = 'INSERT INTO bet365_historic  (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		
		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID,NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseOUEXT($db){
//clearTable('Alternative Total Goals',$db); 


// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ou-ext.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
 $upddate=""; $cronupdated="";
	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if (trim($participant->attributes()->Name) == 'Under') {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == 'Over') {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		
		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		
		
		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array($newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));				
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseOU1H($db){
//clearTable('First Half Goals',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ou-1h.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if (trim($participant->attributes()->Name) == 'Under') {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == 'Over') {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		
		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseOU2H($db){
//clearTable('2nd Half Goals',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ou-2h.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if (trim($participant->attributes()->Name) == 'Under') {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == 'Over') {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		
		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
				
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseTTG($db){
//clearTable('Team Total Goals',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ttgoals.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;

                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				//var_dump($oddseu[$num]);
				//var_dump($homeTeam);
				//var_dump($participant->attributes()->Name);
				$num++;
				if ($participant->attributes()->Name == $homeTeam . ' Under') {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == $awayTeam . ' Over') {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		// if($HomeOddsEU == NULL) $HomeOddsEU = 1.00;
		// if($AwayOddsEU == NULL) $AwayOddsEU = 1.00;

		// if($HomeOddsUK == NULL) $HomeOddsUK = '0/1';
		// if($AwayOddsUK == NULL) $AwayOddsUK = '0/1';		

		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseSBH($db){
//clearTable('To Score in Both Halves',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365sbh.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
 $upddate=""; $cronupdated="";
	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam . " to score in both halves") {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == $awayTeam . " to score in both halves") {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		//echo " " , $DrawOddsName , " " , $DrawOddsEU , " " , "<font size='1'>" , $DrawOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate ,  $upddate, $cronupdated,  "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		
$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate ,  $upddate, $cronupdated,  "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		echo '<BR/>';
	} // foreach $event
	}
}
}



function parseBTTS($db){
//clearTable('Both Teams to Score',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365btts.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
$upddate=""; $cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = "Both Teams To Score";
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == "Yes") {
					$HomeOddsName = "Yes"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "No") {
					$AwayOddsName = "No";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo "BTTS" , " " , $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		
		
$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		echo '<BR/>';
	} // foreach $event
	}
}
}





function parseWTN($db){
//clearTable('To Win To Nil',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365wintonil.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
$upddate=""; $cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam . " to win to nil") {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == $awayTeam . " to win to nil") {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		
		
$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseAH($db){
//clearTable('Asian Handicap',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ah.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam) {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == $awayTeam) {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated,  "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
		
		
$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated,  "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	

		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseAHEXT($db){
//clearTable('Alternative Asian Handicap',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365ah-ext.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam) {
					$HomeOddsName = "Home"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == $awayTeam) {
					$AwayOddsName = "Away";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		
		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}

function parseCS($db){
//clearTable('Clean Sheet',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365cs.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == $homeTeam . ' - Yes') {
					$HomeOddsName = "Yes"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

		echo "</font>";
				}
				if ($participant->attributes()->Name == $awayTeam . ' - No') {
					$AwayOddsName = "No";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;

		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
				}


		
		$betScope = abs($betScope); $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
				
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?
';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap,$newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));	
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseAsianCorners($db){
//clearTable('Asian Total Corners',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365atcorners.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);

// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";$betScope = 999.000;
 $upddate=""; $cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$betScope = $participant->attributes()->Handicap;
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == "Under") {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Over") {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));
		
$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL  , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseAsianCards($db){
//clearTable('Asian Total Cards',$db);
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365atcards.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";$betScope = ""; $upddate=""; $cronupdated="";
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				$cronupdated = date('Y-m-d H:i:s');
				$betScope = $participant->attributes()->Handicap;
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;
				$num++;
				if ($participant->attributes()->Name == "Under") {
					$HomeOddsName = "Under"; 
					$HomeOddsEU = $participant->attributes()->OddsDecimal;
					$HomeOddsUK = $participant->attributes()->Odds;
					$HomeOddsID = $participant->attributes()->ID;
				}
				if ($participant->attributes()->Name == "Over") {
					$AwayOddsName = "Over";
					$AwayOddsEU = $participant->attributes()->OddsDecimal;
					$AwayOddsUK = $participant->attributes()->Odds;
					$AwayOddsID = $participant->attributes()->ID;
				}
			}
		}
		
		echo $newdate , " " , $eventID , " " , $homeTeam , " - " , $awayTeam;
		echo "<font color='#3333dd'>";
		echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";
		echo " " , $AwayOddsName , " " , $AwayOddsEU , " " , "<font size='1'>" , $AwayOddsID , "</font>";
		echo "</font>";
		
		$betscope = "FT"; $bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated,CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?,BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);

                $insert->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

	
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated,CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, BetScope, Home_EU, Home_UK, Home_ID, Draw_EU, Draw_UK, Draw_ID, Away_EU, Away_UK, Away_ID, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?,BetScope=?, Home_EU=?, Home_UK=?, Home_ID=?, Draw_EU=?, Draw_UK=?, Draw_ID=?, Away_EU=?, Away_UK=?, Away_ID=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);

                $insert2->execute(array( $newdate ,  $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam , $bookie , $betname , $betScope, $HomeOddsEU , $HomeOddsUK , $HomeOddsID, NULL , NULL , NULL , $AwayOddsEU , $AwayOddsUK , $AwayOddsID,$homemap,$awaymap));

	

		echo '<BR/>';
	} // foreach $event
	}
}
}


function parseScore($db){
//clearTable('Correct Score',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365score.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";$cs='';$csh='';$csa='';
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
$betname = $market->attributes()->Name;
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				//$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;

				$cs = $participant->attributes()->Name;
				$csname = $cs;
				if(strpos($csname,'#') !== false){
					$pos1 = strpos($csname,'#');
					$res = substr($csname,$pos1+1);
					$res = explode('-',$res);
					if(isset($res[0])){
						$csh = $res[0];
					}
					if(isset($res[1])){
						$csa = $res[1];
					}
				}
				$num++;

				$HomeOddsName = "Home"; 
				$HomeOddsEU = $participant->attributes()->OddsDecimal;
				$HomeOddsUK = $participant->attributes()->Odds;
				$HomeOddsID = $participant->attributes()->ID;

				echo $newdate , " " , $eventID , " " , $homeTeam . " " . $cs;
				echo "<font color='#3333dd'>";
				echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

				echo "</font>";
				
		
		//$betScope = abs($betScope); 
				$bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, CS, CSH, CSA, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, CS=?, CSH=?, CSA=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);
                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap));
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, CS, CSH, CSA, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, CS=?, CSH=?, CSA=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);
                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap));
			}
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}



function parseScore1H($db){
//clearTable('Half Time Correct Score',$db); 

$cronupdated = date('Y-m-d H:i:s');
// Getting the XML feed using SimpleXML - Bet365 three-way football market only
$xml_url= 'http://hsiu.hr/bet365/bet365score-1h.xml';
$xml_content = file_get_contents($xml_url) or die("can't get file");
$xml = simplexml_load_string($xml_content);
// Assigning some placeholder values
$sport = "Football"; $sportid=1; $countryid=0; $formatted_date=""; $time_goal=""; $local_id=""; $visitor_id="";$cs='';$csh='';$csa=''; $betname = 'Half Time Correct Score';
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through leagues
foreach ($xml->xpath('EventGroup') as $eventGroup) { // every separate league is an EventGroup
	$leaguename = (string) $eventGroup->attributes()->Name; // League name
	$leagueID = (string) $eventGroup->attributes()->ID; // League ID
	$explodedtags = explode (" ", $leaguename);	$country = $explodedtags[0];  
	if ($country=="Barclays") $country="England"; if ($country=="Capital") $country="England";
	if ($country=="UK") $country="England"; if ($country=="Community") $country="England";	
					$HomeOddsName = null;
					$HomeOddsEU = null;
					$HomeOddsUK = null;
					$HomeOddsID = null;
					$AwayOddsName = null;
					$AwayOddsEU = null;
					$AwayOddsUK = null;
					$AwayOddsID = null;
// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------Going through matches
	if ($leagueID=='100100' || $leagueID=='200877' || $leagueID=='200878' || $leagueID=='200879' || $leagueID=='201539'|| $leagueID=='100120' || $leagueID=='100121'  || $leagueID=='100122' || $leagueID=='100123' || $leagueID=='100625' || $leagueID=='1' || $leagueID=='100635' || $leagueID=='2' || $leagueID=='100650' || $leagueID=='5' || $leagueID=='100620' || $leagueID=='4' || $leagueID=='100630' || $leagueID=='100605' || $leagueID=='201038' || $leagueID=='100665' || $leagueID=='7') {
	echo "<b>" , $leagueID , " " , $country , " - " , $leaguename , "</b>" , "<BR/>";
	foreach ($eventGroup->Event as $event) { // every separate match is an Event
		$eventName = (string) $event->attributes()->Name;
		$eventID = (string) $event->attributes()->ID;
		$teams = explode (" v ", $eventName); $homeTeam = $teams[0]; $awayTeam = $teams[1];

		// ovdje ide GetMap funkcija, zasebno za $homeTeam i za $awayTeam
		$homemap = getMapName($db,$homeTeam);
		$awaymap = getMapName($db,$awayTeam);
		$eventName = $homeTeam . " - " . $awayTeam;
		
		$originaldate = (string) $event->attributes()->StartTime;
		$newdate = DateTime::createFromFormat('d/m/y H:i:s' , $originaldate)->format('Y-m-d H:i:s');
		
		foreach ($event->Market as $market) { // Market
			$num = 1;
                        $array = array();
			foreach ($market->Participant as $participant) {
				$upddate = (string) $participant->attributes()->LastUpdated;
				//$betScope = (string) $participant->attributes()->Handicap;
				$upddate = DateTime::createFromFormat('d/m/y H:i:s' , $upddate)->format('Y-m-d H:i:s');
				
				$oddsname[$num] = $participant->attributes()->Name;
				$oddseu[$num] = $participant->attributes()->OddsDecimal;

				$cs = $participant->attributes()->Name;
				$csname = $cs;
				if(strpos($csname,'#') !== false){
					$pos1 = strpos($csname,'#');
					$res = substr($csname,$pos1+1);
					$res = explode('-',$res);
					if(isset($res[0])){
						$csh = $res[0];
					}
					if(isset($res[1])){
						$csa = $res[1];
					}
				}
				$num++;

				$HomeOddsName = "Home"; 
				$HomeOddsEU = $participant->attributes()->OddsDecimal;
				$HomeOddsUK = $participant->attributes()->Odds;
				$HomeOddsID = $participant->attributes()->ID;

				echo $newdate , " " , $eventID , " " , $homeTeam . " " . $cs;
				echo "<font color='#3333dd'>";
				echo " " , $HomeOddsName , " " , $HomeOddsEU , " " , "<font size='1'>" , $HomeOddsID , "</font>";

				echo "</font>";
				
		
		//$betScope = abs($betScope); 
				$bookie = "Bet365";
		
		$SQL = 'INSERT INTO bet365_fixtures (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, CS, CSH, CSA, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, CS=?, CSH=?, CSA=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert = $db->prepare($SQL);
                $insert->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap));
				
		$SQL2 = 'INSERT INTO bet365_historic (DateT, FeedUpdated, CronUpdated, Toggle, LeagueID, LeagueCode, Country, LeagueName, EventID, EventName, HomeTeam, AwayTeam, Bookie, BetName, Home_EU, Home_UK, Home_ID, CS, CSH, CSA, mapped_teamname1, mapped_teamname2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
		DateT=?, FeedUpdated=?, CronUpdated=?, Toggle=?, LeagueID=?, LeagueCode=?, Country=?, LeagueName=?, EventID=?, EventName=?, HomeTeam=?, AwayTeam=?, Bookie=?, BetName=?, Home_EU=?, Home_UK=?, Home_ID=?, CS=?, CSH=?, CSA=?, mapped_teamname1=?, mapped_teamname2=?';
		

		$insert2 = $db->prepare($SQL2);
                $insert2->execute(array( $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap, $newdate , $upddate, $cronupdated, "0" , $leagueID , $leagueCode="" , $country , $leaguename , $eventID , $eventName , $homeTeam , $awayTeam, $bookie , $betname , $HomeOddsEU , $HomeOddsUK , $HomeOddsID, $cs,$csh,$csa, $homemap,$awaymap));
		}
		


		echo '<BR/>';
	} // foreach $event
	}
}
}
}


function clearTable($db){
	$prepare = $db->prepare('DELETE FROM bet365_fixtures WHERE DateT < NOW()');
	$prepare->execute();
}
?>
