<?php
   // exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '102400M');
    
	require('retsconfig-production.php');
    require('phrets.php');
	require('mysqlconfig-production.php');
   // require('mysqlconfig-dev.php');
	require('process_status_log_insert.php');
	require('mysqlhandler-2.0.php');

	// Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
	define('NOW', date('Y-m-d H:i:s'));
    define('PROCESS_NAME', 'RETS Verify Status');
	define('PROCESS_ID', '1.13');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/RETS_Verify_Status_'.TODAY.'.log');
    
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
		trace("Establish database connection...");
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();

	
			//Write process start record
			log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);
			
			trace("Select Active and Under Contract listings...");
			$mySql->query('SELECT Property_id, ListingId, Status, PropertyType FROM Real_Listing WHERE Status IN ("A","U","P") AND ListingID>0 ORDER BY PropertyType');
			$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
			//echo '<pre>';print_r($properties); exit;

			// Establish RETS connection
			trace("Establish RETS connection...");
			$rets = new phRETS;
			if (defined(RETS_SERVER_VERSION)) $rets->AddHeader('RETS-Version', 'RETS/'.RETS_SERVER_VERSION);
			$rets->AddHeader("User-Agent", RETS_USERAGENT);
			$retsConnection = $rets->Connect(RETS_LOGIN_URL, RETS_USERNAME, RETS_PASSWORD);
			if (!$retsConnection) throw new Exception('RETS connection failed: '.json_encode($rets->Error()).'.');
			
			$yesterday = Date("Y-m-d",strtotime("-1 days"));
			foreach($properties as $property)
			{
				$ListingId= $property['ListingId'];
				trace("Search for ListingId =".$ListingId." in to the RETS server...");
				$PropertyType= $property['PropertyType'];
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

				$search = $rets->SearchQuery("Property", $className, $retsQuery);

				/* If search returned results */
				if($rets->TotalRecordsFound() == 0) 
				{  
					trace("ListingId =".$ListingId." not found in to the RETS server...");
					trace("Find the Property_id =".$property['Property_id']." corresponding to ListingId =".$ListingId."  in to the RETS_Withdrawn Table...");
					$mySql->query("SELECT * FROM RETS_Withdrawn WHERE Property_id =".$property['Property_id']);
					if ($mySql->getRecordsCount()>0) // Record found 
					{
						trace("Property_id  is Exist in to the RETS_Withdrawn Table...");
						$RETS_Withdrawn_data = $mySql->fetchArray(MYSQL_ASSOC);
					
						trace("Record found so Increment the counter by 1 in the RETS_Withdrawn Table...");
						$Counter = ++$RETS_Withdrawn_data['Counter'];
						
						// Update RETS_Withdrawn table with incremented counter...
						$query="UPDATE `RETS_Withdrawn` SET `Counter` = ". $Counter. " 
						WHERE Property_id =".$property['Property_id'] ." AND Counter>0 AND Modified Like '".$yesterday."%'";
						$mySql->query($query);
					}
					else // Record not found...
					{
						trace("Property_id =".$property['Property_id']." not found in to the RETS_Withdrawn Table...");
						trace("Insert the New Record for Property_id =".$property['Property_id']." in the RETS_Withdrawn Table...");
						$mySql->query("INSERT INTO RETS_Withdrawn SET Property_id =".$property['Property_id']);
					}
				}
				else // Rets record found
				{ 
					trace("ListingId =".$ListingId." found in to the RETS server...");
					trace("Record founded in the RETS server so Delete the ListingId =".$ListingId. " form RETS_Withdrawn table");
					$query="DELETE  FROM `RETS_Withdrawn` WHERE Property_id =".$property['Property_id']. " AND Counter > 0";
					$mySql->query($query);
					trace("Fetching founded ListingId =".$ListingId." record in the RETS server...");
					
					while($data = $rets->FetchRow($search)) 
					{ 
						//echo "in";
						$StreetNumber=$data['StreetNumber'];
						$StreetName=addslashes($data['StreetName']);
						$StatusChangeDate=explode('T',$data['StatusChangeTimestamp']);
						$CloseDate=explode('T',$data['CloseDate']);
						$ClosePrice=round($data['ClosePrice']);

						trace("Fetching Real_Location data corresponding to the founded ListingId =".$ListingId." based on  StreetNumber and StreetName...");
						$mySql->query("SELECT * FROM Real_Location WHERE StreetNumber ='".$StreetNumber."' AND StreetName ='".$StreetName."'");
						//echo "SELECT * FROM Real_Location WHERE StreetNumber ='".$data['StreetNumber']."' AND StreetName ='".$data['StreetName']."'";
						if ($mySql->getRecordsCount()> 0)
						{
							trace("Record Exist in the Real_Location table...");
							trace("Fetching Real_Listing data corresponding to the founded ListingId =".$ListingId." based on  Status only if Real_Location record exist...");
							$mySql->query("SELECT * FROM Real_Listing WHERE Status ='".$data['Status']."'");
							if ($mySql->getRecordsCount()== 0)
							{
								trace("Record not Exist in the Real_Listing table...");
								if($data['Status']=='Sold')
								{
									trace("If RETS property Status= S then ...");
									trace("Fetch the Record in the Real_Listing table based on `ListingId`=".$ListingId);
									$mySql->query("SELECT * FROM Real_Listing WHERE `ListingId`=".$ListingId);	
									if ($mySql->getRecordsCount() > 0)
									{
										trace("Record exist in the Real_Listing so update the Real_Listing table by updated records found in the RETS server...");
										$query="UPDATE `Real_Listing`r  SET r.PreviousStatus =  r.Status, r.Status = 'S', r.StatusChangeDate = '".$StatusChangeDate[0]."', r.ClosePrice = ". $ClosePrice.", r.CloseDate ='".$CloseDate[0]."' WHERE `ListingId`=".$ListingId;	
										$mySql->query($query);
									}

									trace("Fetch the Record in the Location_Listing table based on Property_id=".$property['Property_id']."...");
									$mySql->query("SELECT * FROM Location_Listing WHERE Property_id =".$property['Property_id']);
									if ($mySql->getRecordsCount() > 0)
									{
										trace("Record exist in the `Location_Listing` so update the `Location_Listing` table by updated records found in the RETS server...");
										$query="UPDATE `Location_Listing`  SET  Status = 'S' WHERE Property_id =".$property['Property_id'];
										$mySql->query($query);
									}
								}
								else // Status not S
								{
									trace("If RETS property Status not S then ...");
									trace("Fetch the Record in the Real_Listing table for `ListingId`=".$ListingId."...");
									$mySql->query("SELECT * FROM Real_Listing WHERE `ListingId`=".$ListingId);	
									if ($mySql->getRecordsCount() > 0)
									{
										trace("Record exist in the `Real_Listing` so update the `Real_Listing` table by updated records found in the RETS server...");
										$query="UPDATE `Real_Listing` r  SET r.PreviousStatus =  r.Status, r.Status = '". $data['Status']."', r.StatusChangeDate = '".$StatusChangeDate[0]."' WHERE r.`ListingId`=".$ListingId;	
										$mySql->query($query);
									}
								}
							}
						}
					}
				}
			}
			// Process RETS_Withdrawn records...
			trace("To Process RETS_Withdrawn records Fetch the records from RETS_Withdrawn table WHERE Counter >2 ...");
			$mySql->query("SELECT * FROM RETS_Withdrawn WHERE Counter >2");
			//echo "SELECT * FROM RETS_Withdrawn WHERE Counter >2";
			if ($mySql->getRecordsCount()> 0) // Record found 
			{
				trace("Record exist in the RETS_Withdrawn so update the `Real_Listing` table by updated records found in the RETS_Withdrawn table...");
				$mySql->fetchAllRecords($RETS_Withdrawn_Records, MYSQL_ASSOC);
				//echo '<pre>';print_r($RETS_Withdrawn_Records);
				
				
				/***********************************************************/
			
				/***********************************************************/
				foreach($RETS_Withdrawn_Records as $RETS_Withdrawn_Row )
				{
					$rets_property_id=$RETS_Withdrawn_Row['Property_id'];
					//$query="UPDATE `Real_Listing` `r` SET r.PreviousStatus = '".$RETS_Withdrawn_Records['Status']."', r.Status = 'X', r.StatusChangeDate = '".TODAY."'  WHERE `r`.`ListingId`=".$ListingId;	

					$query="UPDATE `Real_Listing` `r` SET r.PreviousStatus = r.Status, r.Status = 'X', r.StatusChangeDate = '".TODAY."'  WHERE `r`.`Property_id`=".$rets_property_id;
					$mySql->query($query);

					trace("Fetch the Record in the Location_Listing table for RETS_Withdrawn's Property_id=".$rets_property_id."...");
					$mySql->query("SELECT * FROM Location_Listing WHERE Property_id =".$rets_property_id);
					//echo "SELECT * FROM Location_Listing WHERE Property_id =".$rets_property_id;exit;
					if ($mySql->getRecordsCount() > 0)
					{
						trace("Record exist in the Location_Listing so  update the `Location_Listing` table by updated records found in the RETS_Withdrawn table...");
						$query="UPDATE `Location_Listing`  SET  Status = 'X' WHERE Property_id =".$rets_property_id;
						$mySql->query($query);
					}
					trace("SET Counter=-1 in RETS_Withdrawn table...");
					$query="UPDATE `RETS_Withdrawn` SET  Counter=-1 WHERE Property_id = ".$rets_property_id;
					$mySql->query($query);
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
	/*SELECT Property_id, ListingId, Status, PropertyType FROM Real_Listing WHERE Status IN ("A","U","P") AND ListingID>0 AND Property_id not in (SELECT Property_id FROM `RETS_Withdrawn`) ORDER BY PropertyType*/
?>





<?php
   // exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '102400M');
    
	require('retsconfig-production.php');
    require('phrets.php');
	require('mysqlconfig-production.php');
   // require('mysqlconfig-dev.php');
	require('process_status_log_insert.php');
	require('mysqlhandler-2.0.php');

	// Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
	define('NOW', date('Y-m-d H:i:s'));
    define('PROCESS_NAME', 'RETS Verify Status');
	define('PROCESS_ID', '1.13');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/RETS_Verify_Status_'.TODAY.'.log');
    
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
		trace("Establish database connection...");
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();

		$Executed=TODAY;
	
		trace("Fetching data of process 1.11 from database");
		//echo "SELECT * FROM `Process_Execution` WHERE Process_id=1.11 AND Executed LIKE '".$Executed."%'";exit;
		$mySql->query("SELECT * FROM `Process_Execution` WHERE Process_id=1.11 AND Executed LIKE '".$Executed."%'");
	
		//$execute= true;
		if ($mySql->getRecordsCount())
		{
			trace("Record Found...");
			$process_array=$mySql->fetchArray(MYSQL_ASSOC);
			//echo '<pre>'; print_r($process_array);
			$execute=false;
			//$date= date("Y-m-d H:i:s",strtotime("-1hour")); 
			//$date = "2015-03-11 05:15:30"; 
			
			//execute loop while status =0
			while($execute==false)
			{
				//echo '<pre>';print_r($process_array); exit;
				
				if($process_array['Status']==0)
				{
					//echo "in1"; exit;
					trace("process execution status is 0 wait for 15 min ");
					sleep(900);
					$mySql->query("SELECT * FROM `Process_Execution` WHERE Process_id=1.11 AND Executed LIKE '".$Executed."%'");
					$process_array=$mySql->fetchArray(MYSQL_ASSOC);
				}
				else
				{
					// make execute true
					$execute=True;
				}
			}
		
			if($execute)
			{
				//echo "in2"; exit;
				//Write process start record
				log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);
				
				trace("Select Active and Under Contract listings...");
				$mySql->query('SELECT Property_id, ListingId, Status, PropertyType FROM Real_Listing WHERE Status IN ("A","U","P") AND ListingID>0 ORDER BY PropertyType');
				$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
				//echo '<pre>';print_r($properties); exit;

				// Establish RETS connection
				trace("Establish RETS connection...");
				$rets = new phRETS;
				if (defined(RETS_SERVER_VERSION)) $rets->AddHeader('RETS-Version', 'RETS/'.RETS_SERVER_VERSION);
				$rets->AddHeader("User-Agent", RETS_USERAGENT);
				$retsConnection = $rets->Connect(RETS_LOGIN_URL, RETS_USERNAME, RETS_PASSWORD);
				if (!$retsConnection) throw new Exception('RETS connection failed: '.json_encode($rets->Error()).'.');
				
				$yesterday = Date("Y-m-d",strtotime("-1 days"));
				foreach($properties as $property)
				{
					$ListingId= $property['ListingId'];
					trace("Search for ListingId =".$ListingId." in to the RETS server...");
					$PropertyType= $property['PropertyType'];
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

					$search = $rets->SearchQuery("Property", $className, $retsQuery);

					/* If search returned results */
					if($rets->TotalRecordsFound() == 0) 
					{  
						trace("ListingId =".$ListingId." not found in to the RETS server...");
						trace("Find the Property_id =".$property['Property_id']." corresponding to ListingId =".$ListingId."  in to the RETS_Withdrawn Table...");
						$mySql->query("SELECT * FROM RETS_Withdrawn WHERE Property_id =".$property['Property_id']);
						if ($mySql->getRecordsCount()>0) // Record found 
						{
							trace("Property_id  is Exist in to the RETS_Withdrawn Table...");
							$RETS_Withdrawn_data = $mySql->fetchArray(MYSQL_ASSOC);
						
							trace("Record found so Increment the counter by 1 in the RETS_Withdrawn Table...");
							$Counter = ++$RETS_Withdrawn_data['Counter'];
							
							// Update RETS_Withdrawn table with incremented counter...
							$query="UPDATE `RETS_Withdrawn` SET `Counter` = ". $Counter. " 
							WHERE Property_id =".$property['Property_id'] ." AND Counter>0 AND Modified Like '".$yesterday."%'";
							$mySql->query($query);
						}
						else // Record not found...
						{
							trace("Property_id =".$property['Property_id']." not found in to the RETS_Withdrawn Table...");
							trace("Insert the New Record for Property_id =".$property['Property_id']." in the RETS_Withdrawn Table...");
							$mySql->query("INSERT INTO RETS_Withdrawn SET Property_id =".$property['Property_id']);
						}
					}
					else // Rets record found
					{ 
						trace("ListingId =".$ListingId." found in to the RETS server...");
						trace("Record founded in the RETS server so Delete the ListingId =".$ListingId. " form RETS_Withdrawn table");
						$query="DELETE  FROM `RETS_Withdrawn` WHERE Property_id =".$property['Property_id']. " AND Counter > 0";
						$mySql->query($query);
						trace("Fetching founded ListingId =".$ListingId." record in the RETS server...");
						
						while($data = $rets->FetchRow($search)) 
						{ 
							//echo "in";
							$StreetNumber=$data['StreetNumber'];
							$StreetName=addslashes($data['StreetName']);
							$StatusChangeDate=explode('T',$data['StatusChangeTimestamp']);
							$CloseDate=explode('T',$data['CloseDate']);
							$ClosePrice=round($data['ClosePrice']);

							trace("Fetching Real_Location data corresponding to the founded ListingId =".$ListingId." based on  StreetNumber and StreetName...");
							$mySql->query("SELECT * FROM Real_Location WHERE StreetNumber ='".$StreetNumber."' AND StreetName ='".$StreetName."'");
							//echo "SELECT * FROM Real_Location WHERE StreetNumber ='".$data['StreetNumber']."' AND StreetName ='".$data['StreetName']."'";
							if ($mySql->getRecordsCount()> 0)
							{
								trace("Record Exist in the Real_Location table...");
								trace("Fetching Real_Listing data corresponding to the founded ListingId =".$ListingId." based on  Status only if Real_Location record exist...");
								$mySql->query("SELECT * FROM Real_Listing WHERE Status ='".$data['Status']."'");
								if ($mySql->getRecordsCount()== 0)
								{
									trace("Record not Exist in the Real_Listing table...");
									if($data['Status']=='Sold')
									{
										trace("If RETS property Status= S then ...");
										trace("Fetch the Record in the Real_Listing table based on `ListingId`=".$ListingId);
										$mySql->query("SELECT * FROM Real_Listing WHERE `ListingId`=".$ListingId);	
										if ($mySql->getRecordsCount() > 0)
										{
											trace("Record exist in the Real_Listing so update the Real_Listing table by updated records found in the RETS server...");
											$query="UPDATE `Real_Listing`r  SET r.PreviousStatus =  r.Status, r.Status = 'S', r.StatusChangeDate = '".$StatusChangeDate[0]."', r.ClosePrice = ". $ClosePrice.", r.CloseDate ='".$CloseDate[0]."' WHERE `ListingId`=".$ListingId;	
											$mySql->query($query);
										}

										trace("Fetch the Record in the Location_Listing table based on Property_id=".$property['Property_id']."...");
										$mySql->query("SELECT * FROM Location_Listing WHERE Property_id =".$property['Property_id']);
										if ($mySql->getRecordsCount() > 0)
										{
											trace("Record exist in the `Location_Listing` so update the `Location_Listing` table by updated records found in the RETS server...");
											$query="UPDATE `Location_Listing`  SET  Status = 'S' WHERE Property_id =".$property['Property_id'];
											$mySql->query($query);
										}
									}
									else // Status not S
									{
										trace("If RETS property Status not S then ...");
										trace("Fetch the Record in the Real_Listing table for `ListingId`=".$ListingId."...");
										$mySql->query("SELECT * FROM Real_Listing WHERE `ListingId`=".$ListingId);	
										if ($mySql->getRecordsCount() > 0)
										{
											trace("Record exist in the `Real_Listing` so update the `Real_Listing` table by updated records found in the RETS server...");
											$query="UPDATE `Real_Listing` r  SET r.PreviousStatus =  r.Status, r.Status = '". $data['Status']."', r.StatusChangeDate = '".$StatusChangeDate[0]."' WHERE r.`ListingId`=".$ListingId;	
											$mySql->query($query);
										}
									}
								}
							}
						}
					}
				}
				// Process RETS_Withdrawn records...
				trace("To Process RETS_Withdrawn records Fetch the records from RETS_Withdrawn table WHERE Counter >2 ...");
				$mySql->query("SELECT * FROM RETS_Withdrawn WHERE Counter >2");
				//echo "SELECT * FROM RETS_Withdrawn WHERE Counter >2";
				if ($mySql->getRecordsCount()> 0) // Record found 
				{
					trace("Record exist in the RETS_Withdrawn so update the `Real_Listing` table by updated records found in the RETS_Withdrawn table...");
					$mySql->fetchAllRecords($RETS_Withdrawn_Records, MYSQL_ASSOC);
					//echo '<pre>';print_r($RETS_Withdrawn_Records);
					
					
					/***********************************************************/
				
					/***********************************************************/
					foreach($RETS_Withdrawn_Records as $RETS_Withdrawn_Row )
					{
						$rets_property_id=$RETS_Withdrawn_Row['Property_id'];
						//$query="UPDATE `Real_Listing` `r` SET r.PreviousStatus = '".$RETS_Withdrawn_Records['Status']."', r.Status = 'X', r.StatusChangeDate = '".TODAY."'  WHERE `r`.`ListingId`=".$ListingId;	

						$query="UPDATE `Real_Listing` `r` SET r.PreviousStatus = r.Status, r.Status = 'X', r.StatusChangeDate = '".TODAY."'  WHERE `r`.`Property_id`=".$rets_property_id;
						$mySql->query($query);

						trace("Fetch the Record in the Location_Listing table for RETS_Withdrawn's Property_id=".$rets_property_id."...");
						$mySql->query("SELECT * FROM Location_Listing WHERE Property_id =".$rets_property_id);
						//echo "SELECT * FROM Location_Listing WHERE Property_id =".$rets_property_id;exit;
						if ($mySql->getRecordsCount() > 0)
						{
							trace("Record exist in the Location_Listing so  update the `Location_Listing` table by updated records found in the RETS_Withdrawn table...");
							$query="UPDATE `Location_Listing`  SET  Status = 'X' WHERE Property_id =".$rets_property_id;
							$mySql->query($query);
						}
						trace("SET Counter=-1 in RETS_Withdrawn table...");
						$query="UPDATE `RETS_Withdrawn` SET  Counter=-1 WHERE Property_id = ".$rets_property_id;
						$mySql->query($query);
					}
				}

				// Write process complete record
				log_process_status(PROCESS_NAME, PSS_MAIN, 'Process completed', $completeStatus, PROCESS_ID);
			}
		}
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
	/*SELECT Property_id, ListingId, Status, PropertyType FROM Real_Listing WHERE Status IN ("A","U","P") AND ListingID>0 AND Property_id not in (SELECT Property_id FROM `RETS_Withdrawn`) ORDER BY PropertyType*/
?>