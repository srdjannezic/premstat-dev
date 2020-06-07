<?php


	function connection($state){
			$db_host = "localhost";
			$db_name = "premstat";
			$db_user = "premstat";
			$db_pass = "srki1502";
		$pdo = null;
		if($state == 'open'){
			$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
			$stmt = $pdo->prepare('set names utf8');
			$stmt->execute();
		}
		else{
			$pdo = null;
		}

		return $pdo;
	}
	
?>