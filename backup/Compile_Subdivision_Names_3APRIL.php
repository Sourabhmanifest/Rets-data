<?php
	// exit('in');
    //No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    
	require_once('mysqlconfig-production.php');
	//require_once('mysqlconfig-dev.php');
    require_once('mysqlhandler-2.0.php');
	require_once('process_status_log_insert.php');
    
    // Settings
    define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
	define('NOW', date('Y-m-d H:i:s'));
    define('PROCESS_NAME', 'Compile Subdivision Names');
	define('PROCESS_ID', '1.25');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/Compile-Subdivision-Names-'.TODAY.'.log');

	// Process status step
    define('PSS_MAIN', 0);
    
    // Process status
    define('PS_STARTED', 0);
    define('PS_COMPLETED_SUCCESS', 1);
    define('PS_COMPLETED_ERRORS', -1);
    
    //Create log file
	if (LOG_TO_FILE) $flog = fopen(LOG_FILEPATH, 'at') or die ('Failed to open log file (\''.LOG_FILEPATH.'\').');
	trace(PROCESS_NAME.' started...');
	if (DEBUG) trace('WARNING: Debug mode is ON.');

	$completeStatus = PS_COMPLETED_SUCCESS;
    try
    {
        // Establish database connection
		trace('Establish database connection...');
       $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
       $mySql->connect();
        
		// Write process start record
        log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED,PROCESS_ID);
		
		trace('Select all Real_Location records that have not been processed...');

		$mySql->query('SELECT l.Location_id,l.Sub_id,l.Subdivision,l.City_id FROM Real_Location l, tb_City c WHERE l.City_id = c.City_id AND c.Include=1 AND l.dict_Sub = 0 AND NOT ISNULL(l.Subdivision) AND l.Subdivision!="" AND l.Subdivision NOT LIKE "%*%"  AND l.Subdivision!="." AND l.Subdivision!="?"  AND l.Subdivision not REGEXP "^[0-9]+$" ORDER BY 2,3 ');

		if($mySql->getRecordsCount())
		{
			$mySql->fetchAllRecords($Real_Location_properties, MYSQL_ASSOC);
			
			// fetch the keyvalue for explode the subdivision name field
			trace('fetch the keyvalue for explode the subdivision name field WHERE Keyname =AREANAME AND subKeyname= PARSER...');
			$KeyValue = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = \''.AREANAME.'\' AND `subKeyname` = \''.PARSER.'\'',"KeyValue"); 
			if($KeyValue) 
			{
				$keyValue_array=explode(" ",$KeyValue);
				
				foreach($Real_Location_properties as $Real_Location_record)
				{
					subdivision_correct_process($Real_Location_record,$keyValue_array,1);
				}
				/*************************** 1 April *********************************/
				trace("Fetch all the Location_Subdivision record...");
				$mySql->query("SELECT  l.Subdivision, l.Sub_id, l.Location_id, r.City_id FROM Location_Subdivision l ,Real_Location r WHERE  l.Location_id=r.Location_id");
				
				if($mySql->getRecordsCount())
				{
					foreach($Location_Subdivision_properties as $Location_Subdivision_record)
					{
						subdivision_correct_process($Location_Subdivision_record,$keyValue_array,0);
					}
				}
				/******************************************************************/
			}
			trace("Add commonly used Subdivision names to the tb_Subdivision_City table...");
			trace("fetch the THRESHOLD value from SystemSettings table...");
			$threshold_value = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = "AREANAME" AND `subKeyname` ="THRESHOLD"',"KeyValue");
			if($threshold_value)
			{
				$mySql->query('INSERT INTO tb_Subdivision_City (Subdivision, City_id, Count, Entered) SELECT s.Subdivision, l.City_id, Count(s.Subdivision) as Cnt,"'.NOW.'"  FROM Location_Subdivision s, Real_Location l WHERE s.Sub_id=0 AND s.Location_id=l.Location_id GROUP BY l.City_id,s.Subdivision HAVING Cnt>= '.$threshold_value);
			
				$mySql->query('SELECT Sub_id FROM tb_Subdivision_City WHERE Common_id=0');
				$mySql->fetchAllRecords($tb_Subdivision_City_results, MYSQL_ASSOC);
				
				foreach($tb_Subdivision_City_results as $tb_Subdivision_City_row)
				{
					$mySql->query("UPDATE tb_Subdivision_City SET  Common_id= ".$tb_Subdivision_City_row['Sub_id']." WHERE Sub_id = ".$tb_Subdivision_City_row['Sub_id']); 
				}
			}

			trace("fetch the THRESHOLD_2 value from SystemSettings table...");
			$threshold_2_value = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = "AREANAME" AND `subKeyname` ="THRESHOLD_2"',"KeyValue");
			if($threshold_2_value)
			{
				$mySql->query("UPDATE tb_Subdivision_City SET  Accepted= 1 WHERE Count >= ".$threshold_2_value);
			}

			/****************************** 2 april ******************************************/
			
			$mySql->query("SELECT Sub_id, Subdivision, City_id, Length(Subdivision) AS Len  FROM tb_Subdivision_City  WHERE Accepted=1 ORDER BY 3, 4 DESC, 2");
			
			if($mySql->getRecordsCount())
			{
				$mySql->fetchAllRecords($tb_Subdivision_City_properties, MYSQL_ASSOC);
				//echo '<pre>';print_r($tb_Subdivision_City_properties); exit;

				foreach($tb_Subdivision_City_properties as $tb_Subdivision_City_property)
				{
					//echo '<pre>';print_r($tb_Subdivision_City_property); 
					$subdivision=$tb_Subdivision_City_property['Subdivision'];
					$city_id=$tb_Subdivision_City_property['City_id'];
					
					//echo $tb_Subdivision_Mapping_match = $mySql->selectcell('tb_Subdivision_Mapping', 'WHERE `Subdivision` = "'.$subdivision.'" AND `City_id` = '.$city_id,"MaptoName");

					if($tb_Subdivision_Mapping_match)
					{	
						$subdivision=$tb_Subdivision_Mapping_match;
					}
					//echo "SELECT * FROM tb_Subdivision_City  WHERE Accepted=1 AND  City_id=".$city_id." AND Subdivision ='".$subdivision."'";
					
					$mySql->query("SELECT * FROM tb_Subdivision_City  WHERE Accepted=0 AND  City_id=".$city_id." AND Subdivision ='".$subdivision."'");
					
					if($mySql->getRecordsCount())
					{
						$tb_Subdivision_matched_property=$mySql->fetchArray(MYSQL_ASSOC);
						//echo '<pre>';print_r($tb_Subdivision_matched_property); 
						//echo "SELECT * FROM `Location_Subdivision` WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision']."' AND Sub_id=".$tb_Subdivision_matched_property['Sub_id'];

						$mySql->query("SELECT * FROM `Location_Subdivision` WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision']."' AND Sub_id=".$tb_Subdivision_matched_property['Sub_id']);

						if($mySql->getRecordsCount())
						{
							$mySql->fetchAllRecords($Location_Subdivision_matched_properties, MYSQL_ASSOC);
							//echo '<pre>';print_r($Location_Subdivision_matched_properties);

							//echo "UPDATE  Location_Subdivision` SET  Sub_id=".$tb_Subdivision_City_property['Sub_id']."  WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision']."'";

							$mySql->query("UPDATE  Location_Subdivision` SET  Sub_id=".$tb_Subdivision_City_property['Sub_id']."  WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision']."'");
						}
						//echo "<br />";
						//echo "DELETE FROM tb_Subdivision_City WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision'] ."' AND Sub_id=".$tb_Subdivision_matched_property['Sub_id'];
						
						//Delete the matched tb_Subdivision_City record...
						$mySql->query("DELETE FROM tb_Subdivision_City WHERE `Subdivision`='".$tb_Subdivision_matched_property['Subdivision'] ."' AND Sub_id=".$tb_Subdivision_matched_property['Sub_id']);
					}
				}

				foreach($tb_Subdivision_City_properties as $tb_Subdivision_City_property)
				{	
					//echo "<br />";
					//echo "SELECT l.* FROM Location_Subdivision l ,Real_Location r WHERE  l.Location_id=r.Location_id AND r.City_id=".$tb_Subdivision_City_property['City_id']." AND l.Sub_id=0 AND l.Subdivision='".$tb_Subdivision_City_property['Subdivision']."'";

					$mySql->query("SELECT l.* FROM Location_Subdivision l ,Real_Location r WHERE  l.Location_id=r.Location_id AND r.City_id= ".$tb_Subdivision_City_property['City_id']." AND l.Sub_id =0 AND l.Subdivision='".$tb_Subdivision_City_property['Subdivision']."'");
					
					if($mySql->getRecordsCount() )
					{
						$Location_Subdivision_property=$mySql->fetchArray(MYSQL_ASSOC);
						//echo '<pre>';print_r($Location_Subdivision_property);

						/************************************/
						//echo "SELECT * FROM `Location_Subdivision` WHERE `Sub_id`='".$tb_Subdivision_City_property['Sub_id']." AND Location_id= ". $Location_Subdivision_properties['Location_id'];exit;

						$mySql->query("SELECT * FROM `Location_Subdivision` WHERE `Sub_id`='".$tb_Subdivision_City_property['Sub_id']." AND Location_id= ". $Location_Subdivision_properties['Location_id']);

						if($mySql->getRecordsCount())
						{
							$tb_Subdivision_matched_location_id=$mySql->fetchArray(MYSQL_ASSOC);
							$mySql->query("UPDATE  tb_Subdivision_City SET  Count= Count+1 WHERE `Subdivision`='".$tb_Subdivision_City_property['Subdivision'] ."' AND Sub_id=".$tb_Subdivision_City_property['Sub_id']." AND City_id=".$tb_Subdivision_City_property['City_id']);

							//if yes update counter
							$mySql->query("DELETE FROM Location_Subdivision` WHERE `Subdivision`='".$Location_Subdivision_property['Subdivision'] ."' AND Location_id=".$Location_Subdivision_property['Location_id']." AND Sub_id=".$Location_Subdivision_property['Sub_id']);
						}
						else
						{
							$mySql->query("UPDATE Location_Subdivision` SET Sub_id=".$tb_Subdivision_City_property['Sub_id']." WHERE `Subdivision`='".$Location_Subdivision_property['Subdivision'] ."' AND Location_id=".$Location_Subdivision_property['Location_id']." AND Sub_id=".$Location_Subdivision_property['Sub_id']);
						}
					}
				}
				$mySql->query("SELECT  distinct l.Subdivision, l.Sub_id, count(l.Subdivision)as total, r.City_id  FROM Location_Subdivision l ,Real_Location r WHERE  l.Location_id = r.Location_id  group by l.Subdivision");
				if($mySql->getRecordsCount())
				{
					$mySql->fetchAllRecords($Location_Subdivision_records, MYSQL_ASSOC);
					foreach($Location_Subdivision_records as $Location_Subdivision_result)
					{
						$mySql->query("UPDATE tb_Subdivision_City SET Count=".$Location_Subdivision_result['total']." WHERE `Subdivision`='".$Location_Subdivision_result['Subdivision'] ."' AND City_id=".$Location_Subdivision_result['City_id']." AND Sub_id=".$Location_Subdivision_result['Sub_id']);
					}
				}
			}

		/******************************** 2 april  end***************************************/


			trace('UPDATE Location_Subdivision s, tb_Subdivision_City t,Real_Location l SET s.Sub_id = t.Sub_id WHERE s.Subdivision = t.Subdivision AND s.Location_id=l.Location_id AND l.City_id = t.City_id AND s.Sub_id=0');

			$mySql->query('UPDATE Location_Subdivision s, tb_Subdivision_City t,Real_Location l SET s.Sub_id = t.Sub_id WHERE s.Subdivision = t.Subdivision AND s.Location_id=l.Location_id AND l.City_id = t.City_id AND s.Sub_id=0');

			//Process Polygon
			trace("Start Process Polygon...");
			$mySql->query('SELECT c.Polygon_id, s.Subdivision, s.Sub_id, SUM( Count ) AS totalcnt
											FROM Location_Subdivision s, Real_Polygon p, Polygon_Center c
											WHERE s.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id
											GROUP BY c.Polygon_id, s.Subdivision
											ORDER BY c.Polygon_id, s.Subdivision');
			
			if($mySql->getRecordsCount() )
			{
				$mySql->fetchAllRecords($properties, MYSQL_ASSOC);
				foreach($properties as $property)
				{
					trace('find the record in the Polygon_Subdivision  WHERE Polygon_id ='.$property["Polygon_id"] .' AND Subdivision  = "'.$property["Subdivision"]);
					$Polygon_id_value = $mySql->selectcell('Polygon_Subdivision', 'WHERE Polygon_id ='.$property["Polygon_id"] .' AND Subdivision = "'.trim($property["Subdivision"]).'"',"Polygon_id");
					if($mySql->getRecordsCount() )
					{
						//Update Polygon_ Subdivision .Count 
						trace("Record found increment the count value by 1...");
						$mySql->query("UPDATE Polygon_Subdivision SET Count = Count+1 WHERE Polygon_id =".$Polygon_id_value);
					}
					else
					{
						//Add new record in Polygon_Subdivision
						trace("Record not found insert new record in the Polygon_Subdivision table...");
						$mySql->query("INSERT INTO Polygon_Subdivision SET Polygon_id =".$property['Polygon_id'] .", Subdivision  = '".trim($property['Subdivision'])."',Sub_id= ".$property['Sub_id'].", Count= ".$property['totalcnt'].", Entered='".NOW."'");
					}
				}
			}
		}
		// Write process complete record
		log_process_status(PROCESS_NAME, PSS_MAIN, 'Process completed', $completeStatus,PROCESS_ID);
	}

	catch (Exception $e)
    {
		trace('EXCEPTION: '.$e->getMessage());
        $completeStatus = PS_COMPLETED_ERRORS;
        if (DEBUG) print $e->getTraceAsString();
    }


	function subdivision_correct_process($Location_record,$keyValue_array,$update)
	{
		global $mySql;
		trace('start execution for Location_id : '.$Location_record["Location_id"] .' and Subdivision : '.$Location_record["Subdivision"].'...');
		$Subdivision		=	$Location_record['Subdivision'];
		$City_id				=	$Location_record['City_id'];
		$Location_id		=	$Location_record['Location_id'];
		$Sub_id				=	$Location_record['Sub_id'];

		trace("fetch the mapping name for the subdivision '".$Subdivision."' in the tb_Subdivision_Mapping table ...");
		$SubdivisionMapName = $mySql->selectcell('tb_Subdivision_Mapping', 'WHERE `Subdivision` = "'.$Subdivision.'" AND `City_id` = '.$City_id,"MaptoName"); 
		$mapped=0;
		if($SubdivisionMapName)
		{ 
			trace('Mapping the subdivision="'.$Subdivision.'" by the "'.$SubdivisionMapName .'"...');
			$Subdivision=$SubdivisionMapName;
			$mapped=1;
		}
		else 
		{
			trace('No mapping found for the "'.$Subdivision .'"...');
		}
		//call the mapping function for map the matched map string 
		$count=0;
		foreach($keyValue_array as $key_value)
		{
			if($key_value=="or")
			{
				$key_value=" or ";
			}
			if (strpos($Subdivision,$key_value))
			{ 
				trace('Explode the  "'.$Subdivision .'" by the "'.$key_value.'"...');
				$count++;
				$Subdivision_array=explode($key_value,$Subdivision);
				
				foreach($Subdivision_array as $Subdivision_value)
				{
					if($Subdivision_value!="")
					{
						$Subdivision_value=trim($Subdivision_value);
						$Subdivision_value = Subdivision_Mapping($Subdivision_value,$City_id);
						if(strpos($Subdivision_value,","))
						{
							$Subdivision_map_array=explode(",",$Subdivision_value);
							
							foreach($Subdivision_map_array as $Subdivision_map_name)
							{
								trace('Call the function process_subdivision("'.$Subdivision_map_name.'")...');
								$process_subdivision_value=process_subdivision($Subdivision_map_name,$City_id);
								if($process_subdivision_value!="")
								{
									trace('Call the function subdivision_insert_update("'.$process_subdivision_value.'", "'.$Location_id.'","'.$City_id.'")...');
									subdivision_insert_update($process_subdivision_value,$Location_id,$City_id);
								}
							}
						}
						else 
						{
							trace('Call the function process_subdivision("'.$Subdivision_value.'")...');
							$process_subdivision_value=process_subdivision($Subdivision_value,$City_id);
							if($process_subdivision_value!="")
							{
								trace('Call the function subdivision_insert_update("'.$process_subdivision_value, $Location_id, $City_id.'")...');
								subdivision_insert_update($process_subdivision_value,$Location_id,$City_id);
							}
						}
					}
				}
			}
		}
		
		if($count==0)
		{
			//if subdivision not contain any seperate symbol.
			trace('Call the function Subdivision_Mapping("'.$Subdivision.'","'.$City_id.'")...');
			if($mapped==0)
			{
				$Subdivision = Subdivision_Mapping($Subdivision,$City_id);
				if(strpos($Subdivision,","))
				{
					$Subdivision_map_array=explode(",",$Subdivision);
					//echo '<pre>'; print_r($Subdivision_map_array);
					foreach($Subdivision_map_array as $Subdivision_map_name)
					{
						//echo '<pre>'; print_r($Subdivision_map_name);
						trace('Call the function process_subdivision("'.$Subdivision_map_name.'")...');
						$process_subdivision_value=process_subdivision($Subdivision_map_name,$City_id);
						if($process_subdivision_value!="")
						{
							trace('Call the function subdivision_insert_update("'.$process_subdivision_value.'", "'.$Location_id.'","'.$City_id.'")...');
							subdivision_insert_update($process_subdivision_value,$Location_id,$City_id);
						}
					}
				}
				else
				{
					trace('Call the function process_subdivision("'.$Subdivision.'")...');
					$process_subdivision_value=process_subdivision($Subdivision,$City_id);
					if($process_subdivision_value!="")
					{
						trace('Call the function subdivision_insert_update("'.$process_subdivision_value.'", "'.$Location_id.'","'.$City_id.'")...');
						subdivision_insert_update($process_subdivision_value,$Location_id,$City_id);
					}
				}
			}
			else
			{
				trace('Call the function process_subdivision("'.$Subdivision.'")...');
				$process_subdivision_value=process_subdivision($Subdivision,$City_id);
				if($process_subdivision_value!="")
				{
					trace('Call the function subdivision_insert_update("'.$process_subdivision_value.'", "'.$Location_id.'","'.$City_id.'")...');
					subdivision_insert_update($process_subdivision_value,$Location_id,$City_id);
				}
			}
		}
		if($update)
		{
			//UPDATE Real_Location
			trace('UPDATE Real_Location  SET  dict_Sub= 1 WHERE Location_id = '. $Location_id.'...');
			$mySql->query("UPDATE Real_Location SET  dict_Sub= 1 WHERE Location_id = ". $Location_id);
		}
	}


	function process_subdivision($Subdivision_value,$City_id)
	{
		global $mySql;
	
		$Subdivision_value=str_replace("'", "", $Subdivision_value);
		$Subdivision_value = preg_replace('/\s+/', ' ', $Subdivision_value);
		$mySql->query("SELECT KeyValue FROM SystemSettings WHERE Keyname= 'AREANAME' AND subKeyname LIKE 'STRIP%'");
		$mySql->fetchAllRecords($systemsetting_striparray, MYSQL_ASSOC);
		
		foreach($systemsetting_striparray as $systemsetting_strip)
		{
			$systemsetting_strip_array=explode(",",$systemsetting_strip['KeyValue']);
		
			foreach($systemsetting_strip_array as $systemsetting_strip)
			{
				$systemsetting_strip=trim($systemsetting_strip);
				$Subdivision_value=trim($Subdivision_value);
				$systemsetting_strip=" ".$systemsetting_strip;
				$Subdivision_value=" ".$Subdivision_value;
				
			
				if(stripos($Subdivision_value,$systemsetting_strip)>-1)
				{
					trace($systemsetting_strip.'" found in the subdivision  "'.$Subdivision_value.'"...');
					trace('Remove the string from matched string to the end...');
					if(stripos($Subdivision_value,$systemsetting_strip)==0 && strlen($systemsetting_strip)<5)
					{
						trace("Skip Striping from subdvision because stripable string at begining and have length less than 5 char" );
					}
					else
					{
						$Subdivision_value=substr($Subdivision_value, 0, (stripos($Subdivision_value,$systemsetting_strip))); 
						$Subdivision_value=trim($Subdivision_value);
					}
					
					trace('New Subdivision is : "'.$Subdivision_value.'"...');
				}
				if($Subdivision_value=="")
				{
					$Subdivision_value=trim($Subdivision_value);
					return($Subdivision_value);
				}
				if(is_numeric($Subdivision_value))
				{
					$Subdivision_value="";
					$Subdivision_value=trim($Subdivision_value);
					return($Subdivision_value);
				}
			}

		}
		trace("Call the acronym function to replace the acronyms by it full name...");
		$Subdivision_value=trim($Subdivision_value);
		$Subdivision_value_old=strtoupper($Subdivision_value);
		$Subdivision_value=acronym($Subdivision_value);
		if($Subdivision_value_old!=strtoupper($Subdivision_value))
		{
			trace("fetch the mapping name for the subdivision '".$Subdivision_value."' in the tb_Subdivision_Mapping table ...");
			$Subdivision_Map_Name = $mySql->selectcell('tb_Subdivision_Mapping', 'WHERE `Subdivision` = "'.$Subdivision_value.'" AND `City_id` = '.$City_id,"MaptoName"); 
			if($Subdivision_Map_Name)
			{
				trace('Mapping the subdivision="'.$Subdivision_value.'" by the "'.$Subdivision_Map_Name .'"...');
				$Subdivision_value=$Subdivision_Map_Name;
			}
		}
		$Subdivision_value=trim($Subdivision_value);
		trace("final subdivision field value is '".$Subdivision_value."'...");
		return($Subdivision_value);
	}


	function subdivision_insert_update($processedsubdivisionvalue,$Location_id,$City_id)
	{
		global $mySql;
		$processedsubdivisionvalue= trim($processedsubdivisionvalue);

		/********************************23 feb ************************************/
		$mySql->query("SELECT ShortDesc FROM tb_Lookup WHERE ShortDesc!='NULL'");
		if($mySql->getRecordsCount() )
		{ 
			$mySql->fetchAllRecords($tb_Lookup_properties, MYSQL_ASSOC);
			foreach($tb_Lookup_properties as $tb_Lookup)
			{
				$shortdesc = str_replace(" ","", $tb_Lookup['ShortDesc']);
				if($shortdesc==$processedsubdivisionvalue)
				{
					$processedsubdivisionvalue=$tb_Lookup['ShortDesc'];
					$processedsubdivisionvalue= trim($processedsubdivisionvalue);
				}
			}
		}
		/***************************************************************************/
		trace("SELECT * FROM Location_Subdivision WHERE Location_id =". $Location_id." AND Subdivision='".$processedsubdivisionvalue."'");
		$mySql->query("SELECT * FROM Location_Subdivision WHERE Location_id =". $Location_id." AND Subdivision='".$processedsubdivisionvalue."'");
		
		if($mySql->getRecordsCount())
		{
			$Location_Subdivision_properties= $mySql->fetchArray(MYSQL_ASSOC);
			$count=$Location_Subdivision_properties['Count']+1;

			trace("UPDATE Location_Subdivision SET  Count= ".$count ." WHERE Location_id = ".$Location_id." AND Subdivision='".$processedsubdivisionvalue."'");
			$mySql->query("UPDATE Location_Subdivision SET  Count= ".$count ." WHERE Location_id = ".$Location_id." AND Subdivision='".$processedsubdivisionvalue."'");
		}
		else
		{
			trace("SELECT * FROM tb_Subdivision_City WHERE Subdivision='".$processedsubdivisionvalue."' AND City_id=".$City_id);
			$mySql->query("SELECT * FROM tb_Subdivision_City WHERE Subdivision='".$processedsubdivisionvalue."' AND City_id=".$City_id);
			if($mySql->getRecordsCount())
			{ 
				$tb_Subdivision_City_record= $mySql->fetchArray(MYSQL_ASSOC);
				$tb_Sub_id=$tb_Subdivision_City_record['Sub_id'];
				$tb_Count=$tb_Subdivision_City_record['Count'];
				
				$mySql->query("UPDATE tb_Subdivision_City SET Count = ( SELECT count( $tb_Sub_id ) FROM `Location_Subdivision` WHERE `Sub_id` =$tb_Sub_id ) WHERE Sub_id =$tb_Sub_id");

				trace("INSERT INTO Location_Subdivision SET Location_id = ".$Location_id.", Sub_id = ".$tb_Sub_id.", Subdivision='".$processedsubdivisionvalue."', Entered='".NOW."'");
				$mySql->query("INSERT INTO Location_Subdivision SET Location_id = ".$Location_id.", Sub_id = ".$tb_Sub_id.", Subdivision='".$processedsubdivisionvalue."', Entered='".NOW."'");
			}
			else
			{
				trace("INSERT INTO Location_Subdivision SET Location_id = ".$Location_id.", Sub_id = 0, Subdivision='".$processedsubdivisionvalue."', Entered='".NOW."'");
				$mySql->query("INSERT INTO Location_Subdivision SET Location_id = ".$Location_id.", Sub_id = 0, Subdivision='".$processedsubdivisionvalue."', Entered='".NOW."'"); 
			}
		}
	}

	function acronym($Subdivision_value)
	{
		global $mySql;
		//remove ACRONYM
		trace("SELECT KeyValue FROM SystemSettings WHERE Keyname= 'AREANAME' AND subKeyname LIKE 'ACRONYM%'...");
		$mySql->query("SELECT KeyValue FROM SystemSettings WHERE Keyname= 'AREANAME' AND subKeyname LIKE 'ACRONYM%'");
		$mySql->fetchAllRecords($systemsetting_acronymarray, MYSQL_ASSOC);
		$acronymfound=0;

		foreach($systemsetting_acronymarray as $systemsetting_acronym)
		{
			$acronym_array =explode(",",$systemsetting_acronym['KeyValue']);
			$lastacronym=trim(end($acronym_array)); 
			foreach($acronym_array as $acronym )
			{
				$acronym=strtoupper(trim($acronym)); 
				$Subdivision_value_ex_array=explode(" ",$Subdivision_value);
				foreach($Subdivision_value_ex_array as $key=>$Subdivision_value_ex)
				{
					$Subdivision_value_ex =trim($Subdivision_value_ex);
					$Subdivision_value_ex=strtoupper($Subdivision_value_ex);
					
					if($Subdivision_value_ex==$acronym)
					{
						$acronymfound++;
						trace("ACRONYM= '".$acronym."' found in the '".$Subdivision_value_ex."' so we need to replace the founded acronym with the  '".$lastacronym."' ...");
						$Subdivision_value_ex=$lastacronym;
						$Subdivision_value_ex_array[$key]=$Subdivision_value_ex; 
						trace("After replace the founded acronym with the full form Subdivision is '".$Subdivision_value_ex_array[$key]."'...");
					}
					$Subdivision_value=implode(" ",$Subdivision_value_ex_array); 
					$Subdivision_value = preg_replace('/\s+/',' ',$Subdivision_value);
				}
			}
		}
		if($acronymfound==0)
		{
			trace("No any acronym found...");
		}
		$Subdivision_value=str_replace("&", "AND", $Subdivision_value);
		$Subdivision_value=trim($Subdivision_value);
		return($Subdivision_value);
	}

	function Subdivision_Mapping($subdivision,$city_id)
	{
		global $mySql;
		trace("fetch the map name to the subdivision value...");
	
		$mySql->query('SELECT MaptoName,LENGTH(Subdivision) AS LEN FROM tb_Subdivision_Mapping WHERE "'.$subdivision.'" LIKE CONCAT("% ",Subdivision," %") AND Contains = 1 ORDER BY LEN DESC');
		if($mySql->getRecordsCount())
		{
			$mySql->fetchAllRecords($tb_Subdivision_Mapping_record, MYSQL_ASSOC);
			$str_array=array();
			$mapname=array();
			foreach($tb_Subdivision_Mapping_record as $tb_Subdivision_Mapping_result)
			{
				$str_array[]=$tb_Subdivision_Mapping_result['MaptoName'];
			}
			$size=count($str_array);
			for($i=0; $i<$size;$i++)
			{
				for($j=$i+1;$j<$size;$j++)
				{	
					if(stripos($str_array[$i],$str_array[$j])>-1)
					{
						$str_array[$j]=""; 
					}
				}
			}
			for($i=0; $i<$size;$i++)
			{
				if($str_array[$i]!="")
				{
					$mapname[$i]=$str_array[$i];
				}
			}
			$subdivision=implode(",",$mapname);
		}
		else
		{
			trace("fetch the map name to the subdivision value...");
			$tb_Subdivision_Mapping_record = $mySql->selectcell('tb_Subdivision_Mapping', 'WHERE `Subdivision` = "'.$subdivision.'" AND `City_id` = "'.$city_id.'"',"MaptoName");
			if($tb_Subdivision_Mapping_record)
			{
				trace('Mapping the subdivision="'.$subdivision.'" by the "'.$tb_Subdivision_Mapping_record .'"...');
				$subdivision=$tb_Subdivision_Mapping_record;
			}
			else 
			{
				$tb_Subdivision_Mapping_result = $mySql->selectcell('tb_Subdivision_Mapping', 'WHERE `Subdivision` = "'.$subdivision.'" AND `City_id` = 0',"MaptoName");
				if($tb_Subdivision_Mapping_result)
				{
					trace('Mapping the subdivision="'.$subdivision.'" by the blank space "'.$tb_Subdivision_Mapping_result .'"...');
					$subdivision=$tb_Subdivision_Mapping_result;
				}
			}
		}
		return($subdivision);
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


		