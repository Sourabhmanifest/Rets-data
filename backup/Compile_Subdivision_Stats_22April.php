<?php
    //exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '102400M');
    
	require('mysqlconfig-production.php');
    //require('mysqlconfig-dev.php');
	require('process_status_log_insert.php');
	require('mysqlhandler-2.0.php');
    
	// Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
    define('PROCESS_NAME', 'Compile Subdivision Stats');
	define('PROCESS_ID', '3.12');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
	define('LOG_FILEPATH', './logs/Compile_Subdivision_Stats_'.TODAY.'.log');
    
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

		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Subdivision_Stats_tmp' ")) ==1) 
		{
			trace('Drop Subdivision_Stats_3 table');
			$mySql->query("DROP TABLE Subdivision_Stats_tmp");
		}
		//  CREATE Baseline_Qwords_tmp...
		trace('Create Subdivision_Stats_tmp....');
		$flag=$mySql->query("CREATE TABLE `Subdivision_Stats_tmp` SELECT * from Subdivision_Stats LIMIT 0");
		if(!$flag)
		{
			trace($mySql_error());
		}
		else
		{
			trace('Retrieve the Subdivision records to process...');
			$last_three_year= date("Y",strtotime("-3 year")); 
			$mySql->query('SELECT Sub_id, Common_id, Subdivision FROM tb_Subdivision_City WHERE Sub_id = Common_id AND Accepted = 1');
			if ($mySql->getRecordsCount())
			{
				$mySql->fetchAllRecords($properties_array, MYSQL_ASSOC);
				foreach($properties_array as $property_row)
				{
					//echo '<pre>';print_r($property_row);
					$sub_id=$property_row['Sub_id'];
					
					$mySql->query("INSERT INTO Subdivision_Stats_Active_tmp  (Sub_id, Subdivision,Year, PropertyType, MinPrice, MaxPrice, AvgPrice, TotalListings) 
					SELECT c.Sub_id, c.Subdivision, '5000', r.PropertyType, MIN(r.ClosePrice) as MinPrice, MAX(r.ClosePrice) as MaxPrice, 
					ROUND(Avg(r.ClosePrice),0) as AvgPrice, count(*) as TotalListings
					FROM Real_Listing r, Real_Polygon p, Polygon_Subdivision s, tb_Subdivision_City c
					WHERE r.Location_id = p.Location_id AND p.Polygon_id = s.Polygon_id AND s.Sub_id = c.Sub_id AND LEFT(r.CloseDate,4)>= $last_three_year 
					AND c.Common_id = $sub_id AND r.Status IN ('A','U') GROUP BY r.PropertyType");
					
					
					$mySql->query("INSERT INTO Subdivision_Stats_Sold_tmp (Sub_id, Subdivision,Year, PropertyType, MinPrice, MaxPrice, AvgPrice, TotalSales)
					SELECT c.Sub_id, c.Subdivision, LEFT(r.CloseDate,4) AS Year, r.PropertyType, MIN(r.ClosePrice) as MinPrice, MAX(r.ClosePrice) as MaxPrice,
					ROUND(Avg(r.ClosePrice),0) as AvgPrice, count(*) as TotalSales
					FROM Real_Listing r, Real_Polygon p, Polygon_Subdivision s, tb_Subdivision_City c
					WHERE r.Location_id = p.Location_id AND p.Polygon_id = s.Polygon_id AND s.Sub_id = c.Sub_id AND LEFT(CloseDate,4)>= $last_three_year  
					AND c.Common_id = $sub_id AND r.Status='S'
					GROUP BY LEFT(CloseDate,4),r.PropertyType ");
				}
			}

			if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Subdivision_Stats_3' ")) ==1) 
			{
				trace('Drop Subdivision_Stats_3 table');
				$mySql->query("DROP TABLE Subdivision_Stats_3");
			}
			if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Subdivision_Stats_2' ")) ==1) 
			{
				trace('ALTER TABLE Subdivision_Stats_2 RENAME TO Subdivision_Stats_3');
				$mySql->query("ALTER TABLE Subdivision_Stats_2 RENAME TO Subdivision_Stats_3");
			}
			if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Subdivision_Stats_1' ")) ==1) 
			{
				trace('ALTER TABLE Subdivision_Stats_1 RENAME TO Subdivision_Stats_2');
				$mySql->query("ALTER TABLE Subdivision_Stats_1 RENAME TO Subdivision_Stats_2");
			}
			if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Subdivision_Stats'")) ==1) 
			{
				trace('ALTER TABLE Subdivision_Stats RENAME TO Subdivision_Stats_1');
				$mySql->query("ALTER TABLE Subdivision_Stats RENAME TO Subdivision_Stats_1");
				trace('ALTER TABLE Subdivision_Stats_tmp RENAME TO Subdivision_Stats');
				$mySql->query("ALTER TABLE Subdivision_Stats_tmp RENAME TO Subdivision_Stats");
			}
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

?>