<?php
   // exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '102400M');
    
	require('retsconfig-production.php');
    require('phrets.php');
	require('mysqlconfig-production.php');
    //require('mysqlconfig-dev.php');
	require('process_status_log_insert.php');
	require('mysqlhandler-2.0.php');

	// Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
	define('NOW', date('Y-m-d H:i:s'));
    define('PROCESS_NAME', 'Update Real Listing Missing Listprice');
	define('PROCESS_ID', '');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/update_real_listing_missing_list_price_'.TODAY.'.log');
    
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

		
		trace("Get all the records from Real_Listing table have ListPrice=0 AND ListingId>0..." );
		$mySql->query('SELECT r.ListingId,r.Location_id,r.ListPrice,r.PropertyType,l.StreetNumber, l.StreetName FROM Real_Listing r , Real_Location l Where  r.ListPrice=0 AND r.ListingId>0 AND r.Location_id=l.Location_id');
	
		//$execute= true;
		if ($mySql->getRecordsCount()> 0)
		{
			$total_records=count($mySql->getRecordsCount());
			trace("Total $total_records Records are found...");
			$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
			//echo '<pre>';print_r($properties); exit;
			
			// Establish RETS connection
			trace("Establish RETS database connection..." );
			$rets = new phRETS;
			if (defined(RETS_SERVER_VERSION)) $rets->AddHeader('RETS-Version', 'RETS/'.RETS_SERVER_VERSION);
			$rets->AddHeader("User-Agent", RETS_USERAGENT);
			$retsConnection = $rets->Connect(RETS_LOGIN_URL, RETS_USERNAME, RETS_PASSWORD);
			if (!$retsConnection) throw new Exception('RETS connection failed: '.json_encode($rets->Error()).'.');
			
			foreach($properties as $property)
			{
				//echo '<pre>';print_r($property); exit;
				$ListingId= $property['ListingId'];
				$PropertyType= $property['PropertyType'];
				$StreetNumber= $property['StreetNumber'];
				$StreetName= $property['StreetName'];

				$retsQuery="(Matrix_Unique_id=".$ListingId.")"; 
				
				if($PropertyType=='RES' || $PropertyType=='COND')
				{
					$className='RESI';
				}
				if($PropertyType=='LND')
				{
					$className='LAND';
				}
				if($PropertyType=='INC')
				{
					$className='INCOME';
				}
				trace("start searching record for ListingId=$ListingId in the RETS server...");
				$search = $rets->SearchQuery("Property", $className, $retsQuery);

				/* If search returned results */
				if($rets->TotalRecordsFound()) 
				{  
					while($data = $rets->FetchRow($search)) 
					{ 
						if($data['StreetNumber']==$StreetNumber && $data['StreetName']==$StreetName)
						{
							$ClosePrice=round($data['ClosePrice']);
							$ListPrice=round($data['ListPrice']);
							if($ListPrice==$ClosePrice)
							{
								$OriginalListPrice=$ListPrice;
							}
							else 
							{
								$OriginalListPrice=0;
							}
							trace("Update Real_Listing Record for ListingId =". $ListingId."...");
							/*echo '<br />';
							echo "UPDATE Real_Listing  SET ListPrice =  $ListPrice, OriginalListPrice = $OriginalListPrice WHERE ListingId = $ListingId";
							echo '<br />';*/
							$mySql->query("UPDATE Real_Listing  SET ListPrice =  $ListPrice, OriginalListPrice = $OriginalListPrice WHERE ListingId = $ListingId");	
						}
					}
				}
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