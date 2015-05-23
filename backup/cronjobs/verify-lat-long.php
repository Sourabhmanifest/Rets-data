<?php
    // exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '102400M');
    
	//require('mysqlconfig-production.php');
    require('mysqlconfig-dev.php');
	require('process_status_log_insert.php');
	require('mysqlhandler-2.0.php');
    
	// Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
    define('PROCESS_NAME', 'Verify lat long');
	define('PROCESS_ID', '');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
	define('LOG_FILEPATH', './logs/verify_lat_long_'.TODAY.'.log');
    
    // Process status step
    define('PSS_MAIN', 0);
    
    // Process status
    define('PS_STARTED', 0);
    define('PS_COMPLETED_SUCCESS', 1);
    define('PS_COMPLETED_ERRORS', -1);
    
    // Create log file
	if (LOG_TO_FILE) $flog = fopen(LOG_FILEPATH, 'at') or die ('Failed to open log file (\''.LOG_FILEPATH.'\').');
	trace(PROCESS_NAME.' started...');
	if (DEBUG) trace('WARNING: Debug mode is ON.');
	$completeStatus = PS_COMPLETED_SUCCESS;

	try
	{
		// Establish database connection
		trace("Establish database connection..." );
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();
	
		 //Write process start record
		log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);

		$mySql->query('SELECT r.Location_id, r.FullAddress FROM Real_Listing r, Real_Location l WHERE r.FullAddress!="" AND l.Lat_Long_Verified=0 AND r.Location_id=l.Location_id LIMIT 2500');
		if ($mySql->getRecordsCount())
		{
			echo "<br />";
			echo "next try....."
			echo "<br />";
			$mySql->fetchAllRecords($properties_array, MYSQL_ASSOC);
			foreach($properties_array as $property_row)
			{
				// echo "<pre>"; print_r($property_row); 
				$location_id=$property_row['Location_id'];
				$fulladdress=$property_row['FullAddress'];
				$latlong		=   get_lat_long($fulladdress); // create a function with the name "get_lat_long" given as below
				$map			=   explode(',' ,$latlong);
				$mapLat		=   $map[0];
				$mapLong	=   $map[1]; 
				if($mapLat != "" ||$mapLong != "")
				{
					trace('UPDATE Real_Location with new lat long...');
					echo "UPDATE Real_Location SET  Latitude= '$mapLat', Longitude='$mapLong', Lat_Long_Verified=1 WHERE Location_id = ". $location_id;
					echo '<br />';
					$mySql->query("UPDATE Real_Location SET  Latitude= '$mapLat' ,Longitude='$mapLong', Lat_Long_Verified=1 WHERE Location_id = ". $location_id);
				}
			}
		}
		do
		{
			$mySql->query('SELECT r.Location_id, r.FullAddress FROM Real_Listing r, Real_Location l WHERE r.FullAddress!="" AND l.Lat_Long_Verified=0 AND (l.Latitude="" OR l.Longitude="")AND r.Location_id=l.Location_id LIMIT 2500');
			if ($mySql->getRecordsCount())
			{
				$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
				foreach($properties as $property)
				{
					// echo "<pre>"; print_r($property); 
					$location_id=$property['Location_id'];
					$fulladdress=$property['FullAddress'];
					$latlong		=   get_lat_long($fulladdress); // create a function with the name "get_lat_long" given as below
					$map			=   explode(',' ,$latlong);
					$mapLat		=   $map[0];
					$mapLong	=   $map[1]; 
					trace('UPDATE Real_Location with new lat long...');
					echo "UPDATE Real_Location SET  Latitude= '$mapLat' ,Longitude='$mapLong', Lat_Long_Verified=1 WHERE Location_id = ". $location_id;
					echo '<br />';
					$mySql->query("UPDATE Real_Location SET  Latitude= '$mapLat' ,Longitude='$mapLong', Lat_Long_Verified=1 WHERE Location_id = ". $location_id);
				}
			}
			$mySql->query('SELECT r.Location_id, r.FullAddress FROM Real_Listing r, Real_Location l WHERE r.FullAddress!="" AND (l.Latitude="" OR l.Longitude="") AND l.Location_id=r.Location_id LIMIT 100');
			
			while($mySql->getRecordsCount()>0)
		}
		// Write process complete record
		log_process_status(PROCESS_NAME, PSS_MAIN, 'Process completed', $completeStatus, PROCESS_ID);
	}

	catch (Exception $e)
	{
		trace('EXCEPTION: '.$e->getMessage());
		$completeStatus = PS_COMPLETED_ERRORS;
		if (DEBUG) print $e->getTraceAsString();
	}

	trace(PROCESS_NAME.' completed.');
	if (LOG_TO_FILE and $flog) fclose($flog);
	$mySql->close();

	// Logs message
	function trace($_msg)
	{
		global $flog;
		$now = date('Y-m-d H:i:s');
		if (LOG_TO_CONSOLE) print($now.': '.$_msg.END_OF_LINE);
		if (LOG_TO_FILE) fwrite($flog, $now.': '.$_msg.END_OF_LINE);
	}

// function to get  the address
function get_lat_long($address)
{	
	//sleep(1);
    $address = str_replace(" ", "+", $address);
    $json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=$region");
    $json = json_decode($json);
	// echo "<pre>"; print_r($json); 

    $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
    return $lat.','.$long;
}
?>