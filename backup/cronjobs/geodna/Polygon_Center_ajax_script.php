<?php
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
$Polygon_id=$_GET['Polygon_id'];

$mySql->query('UPDATE `Polygon_Center` SET `geodna`=\''.$final_geodna.'\' WHERE `Polygon_id`=\''.$Polygon_id.'\'');

?>