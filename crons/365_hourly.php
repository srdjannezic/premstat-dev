<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require ABSPATH . 'core/config/database.php';
require ABSPATH . 'core/parsers/bet365.php';

$pdo = connection('open');
parseScore1H($pdo);
parseScore($pdo);
parse1x2($pdo);
parse1x2half($pdo);
parseOU($pdo);
parseOUEXT($pdo);
parseOU1H($pdo);
parseOU2H($pdo);
parseSBH($pdo);
parseTTG($pdo);
parseBTTS($pdo);
parseWTN($pdo);
parseAHEXT($pdo);
parseAH($pdo);
parseCS($pdo);
parseAsianCorners($pdo);
parseAsianCards($pdo);

clearTable($pdo);


$pdo = connection('close');
?>
