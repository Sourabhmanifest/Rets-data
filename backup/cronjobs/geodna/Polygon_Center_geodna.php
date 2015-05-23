<!-- <script type="text/javascript" src="geodna/geodna.js"></script>
<script type="text/javascript" src="geodna/jquery.js"></script>
<script>

	function create_geodna(Polygon_id,latitude,longitude)
	{
		var geodna = GeoDNA.encode(latitude,longitude);
		
		$.ajax({
			type: "GET",
			url: "geodna/Polygon_Center_ajax_script.php?geodna="+geodna+"&Polygon_id="+Polygon_id
		});
	}

</script> -->

<?php
    //exit("in");

    // No time limit for this script execution
   // set_time_limit(0);
    //ini_set('memory_limit', '1024M');

	//require('mysqlconfig-production.php');
	//require('mysqlconfig-dev.php');
	//require('mysqlhandler-2.0.php');
    
	//Establish database connection
    //$mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
	//$mySql->connect();
      
     //Get the value of latitude and longitude
	$mySql->query('SELECT Polygon_id,Latitude,Longitude FROM `Polygon_Center` WHERE GeoDNA!="" OR GeoDNA IS NOT NULL limit 10');
	$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
	//echo "<pre>";print_r($properties);exit;
	foreach($properties as $property)
	{
		//echo '<pre>'; print_r($property);exit;
		$polygon_id=$property['Polygon_id'];
		$latitude=$property['Latitude'];
		$longitude=$property['Longitude'];
		
		$final_geodna=encode( $latitude, $longitude);
		//echo 'UPDATE `Polygon_Center` SET geodna="'.$final_geodna.'" WHERE Polygon_id='.$polygon_id; echo '<br />';
		$mySql->query('UPDATE `Polygon_Center` SET geodna="'.$final_geodna.'" WHERE Polygon_id='.$polygon_id);
		//echo '<script type="text/javascript">create_geodna('.$property["Polygon_id"] .','.$property["Latitude"] .','.$property["Longitude"].');</script>';
	}
	
?>