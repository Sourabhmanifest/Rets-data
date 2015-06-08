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
    define('PROCESS_NAME', 'Set Active Location');
	define('PROCESS_ID', '1.12');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/set_active_location'.TODAY.'.log');
    
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
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();
	
		 //Write process start record
        log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);
		

		trace("Fetching data of process 1.11 from database");
		$mySql->query('SELECT * FROM `Process_Execution`WHERE Process_id = 1.11');
		$process_array=$mySql->fetchArray(MYSQL_ASSOC);
		//echo '<pre>';print_r($process_array);
		
		

		if(count($process_array))
		{
			$execute=false;
			$Timebefore15min= date("Y-m-d H:i:s",strtotime("-15minutes"));
			$currenttime=date("Y-m-d H:i:s");

			//execute loop while status =0
			while($execute==false)
			{
				if($process_array['Status']==0)
				{
					if($process_array['Executed']<=$currenttime && $process_array['Executed']>=$Timebefore15min)
					{
						trace("wait for 5 minutes..");
						sleep(5*60);
					}
					else
					{
						$execute=True;
					}
				}
				else
				{
					$execute=True;
				}
			}

			if($execute == True)
			{
				//Initialize
				$mySql->query("UPDATE Real_Location SET Active = 0 WHERE Active = 1");

				//Set Active Flag
				$mySql->query("UPDATE Real_Location l, Real_Listing r, tb_City c 
				SET l.Active = 1 
				WHERE l.Location_id = r.Location_id AND l.City_id = c.City_id AND c.Include=1 AND r.Status IN ('A','U') ");
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