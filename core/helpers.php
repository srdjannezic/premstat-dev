<?php

function isCLI(){
	 return (php_sapi_name() === 'cli');
}

function isUrlExists($url){
	$file_headers = @get_headers($url);
	$exists = '';
	if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
	    $exists = false;
	}
	else {
	    $exists = true;
	}
	return $exists;
}

function getCurrentSeason(){

}

function getAllSeasons(){
	$seasons = array();

	for ($year1=0; $year1 < 50; $year1++) { 

		$year1 = $year1;
		$year2 = $year1+1;
	
		($year1 < 10) ? $year1 = "0".$year1 : $year1;
		($year2 < 10) ? $year2 = "0".$year2 : $year2;

		$seasons[] = $year1.$year2;
	}

	return $seasons;
}

function getURL($url){
	//  Initiate curl
	$ch = curl_init();
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL,$url);
	// Execute
	$result=curl_exec($ch);
	// Closing
	curl_close($ch);

	return $result;
}


function getMapName($db,$name){
	$select = $db->prepare('select Acronym,Shortname, Longname from map where ? in ( `Acronym`, `Shortname`, `Longname`, `Alt1`, `Alt2`, `Alt3`, `Alt4`, `Alt5`, `Alt6`)');
	$select->execute(array($name));
	
	$row = $select->fetch();
	
	return $row['Acronym'];
}

?>