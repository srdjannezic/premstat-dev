<?php

require ABSPATH . 'core/downloader.php';

function parseCSV($file,$db){
	$row = 1;
	if (($handle = fopen($file, "r")) !== FALSE) {
	  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	    $num = count($data);
	    echo "<p> $num fields in line $row: <br /></p>\n";
	    $row++;
	    if($row > 1){ //first are columns
		sendToDB($data,$db);
            } 
	  }
	  fclose($handle);
	}
}

function sendToDB($data,$db){
	    $insert = $db->prepare('INSERT INTO `fivethirtyeight_fixtures`(`date`, `league_id`, `league`, `team1`, `team2`, `spi1`, `spi2`, `prob1`, `prob2`, `probtie`, `proj_score1`, `proj_score2`, `importance1`, `importance2`, `score1`, `score2`, `xg1`, `xg2`, `nsxg1`, `nsxg2`, `adj_score1`, `adj_score2`,`mapped_teamname1`,`mapped_teamname2`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `date`=?, `league_id`=?, `league`=?, `team1`=?, `team2`=?, `spi1`=?, `spi2`=?, `prob1`=?, `prob2`=?, `probtie`=?, `proj_score1`=?, `proj_score2`=?, `importance1`=?, `importance2`=?, `score1`=?, `score2`=?, `xg1`=?, `xg2`=?, `nsxg1`=?, `nsxg2`=?, `adj_score1`=?, `adj_score2`=?,`mapped_teamname1`=?,`mapped_teamname2`=?');

$mapteam1 = getMapName($db,$data[3]);
$mapteam2 = getMapName($db,$data[4]);
	    $insert->execute(array($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14],$data[15],$data[16],$data[17],$data[18],$data[19],$data[20],$data[21],$mapteam1,$mapteam2,$data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14],$data[15],$data[16],$data[17],$data[18],$data[19],$data[20],$data[21],$mapteam1,$mapteam2));
}

?>
