<!--<script type="text/javascript" src="geodna/geodna.js"></script>
<script type="text/javascript" src="geodna/jquery.js"></script>
<script>

	function create_geodna(location_id,latitude,longitude)
	{
		var geodna = GeoDNA.encode(latitude,longitude);
		
		$.ajax({
			type: "GET",
			url: "http://realmarkable.com/cronjobs/geodna/geodna_ajax_script.php?geodna="+geodna+"&location_id="+location_id
		});
	}

</script>-->

<?php
    //exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '1024M');

	require('process_status_log_insert.php');
	require('mysqlconfig-production.php');
	//require('mysqlconfig-dev.php');
	require('mysqlhandler-2.0.php');
	require('encode_geodna.php');
	
    define('PROCESS_NAME', 'Compute geoDNA');
    define('LOG_PROCESS_STATUS', true);
	define('PROCESS_ID', 1.31);
	 // Process status step
    define('PSS_MAIN', 0);
    
    // Process status
    define('PS_STARTED', 0);
    define('PS_COMPLETED_SUCCESS', 1);
    define('PS_COMPLETED_ERRORS', -1);

	//Establish database connection
    $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
	$mySql->connect();
 
     // Write process start record
	if (LOG_PROCESS_STATUS) log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED,PROCESS_ID);

     //Get the value of latitude and longitude
	$mySql->query('SELECT Location_id, Latitude,Longitude FROM `Real_Location` WHERE geodna="" OR geodna IS NULL ');
	$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
	//echo "<pre>";print_r($properties); exit;
	
	foreach($properties as $property)
	{
		//echo '<pre>'; print_r($property);exit;
		$location_id=$property['Location_id'];
		$latitude=$property['Latitude'];
		$longitude=$property['Longitude'];
		
		$final_geodna=encode( $latitude, $longitude);
		//echo '<script type="text/javascript">create_geodna('.$property["Location_id"] .','.$property["Latitude"] .','.$property["Longitude"].');</script>';
		//echo 'UPDATE Real_Location SET geodna="'.$final_geodna.'" WHERE Location_id='.$location_id;
		//echo '<br>';
		$mySql->query('UPDATE Real_Location SET geodna="'.$final_geodna.'" WHERE Location_id='.$location_id);
	}
	 // Write process completed record
	log_process_status(PROCESS_NAME, PSS_MAIN, 'Process completed', PS_COMPLETED_SUCCESS,PROCESS_ID);
	//$mySql->close();
?>