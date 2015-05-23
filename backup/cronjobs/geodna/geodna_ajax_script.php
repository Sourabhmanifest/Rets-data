<?php
/*$file = fopen("geodna_status.txt","a") or exit("Unable to open file!");
fwrite($file,"helloooooooooooooo");
fclose($file);exit;
exit;*/
// No time limit for this script execution
set_time_limit(0);
ini_set('memory_limit', '1024M');

require('../mysqlconfig-production.php');
//require('../mysqlconfig-dev.php');
require('../mysqlhandler-2.0.php');

//Establish database connection
$mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
$mySql->connect();

$final_geodna=$_GET['geodna'];
$location_id=$_GET['location_id'];

$mySql->query('UPDATE `Real_Location` SET `geodna`=\''.$final_geodna.'\' WHERE `Location_id`=\''.$location_id.'\'');
				
?>