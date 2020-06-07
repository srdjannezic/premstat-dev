<?php
require 'helpers.php';


function getFootballData(){
	$seasons = getAllSeasons();

	foreach ($seasons as $season) {

		$url = "http://www.football-data.co.uk/mmz4281/{$season}/E0.csv";
		
		if(isUrlExists($url)){
			file_put_contents(__DIR__."/feeds/fdata/historic/{$season}E0.csv", fopen($url, 'r'));
		}
		else{
			break;
		}
	}
}


function get538(){
		$url = "https://projects.fivethirtyeight.com/soccer-api/club/spi_matches.csv";
		
		if(isUrlExists($url)){
			file_put_contents(__DIR__."/feeds/538/fixtures.csv", fopen($url, 'r'));
		}   
}
?>
