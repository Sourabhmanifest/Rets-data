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
    define('PROCESS_NAME', 'FindMore Batch');
	define('PROCESS_ID', '2.13');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/findmore_batch'.TODAY.'.log');
    
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
	
		 // Write process start record
        log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);

		// Initialize
		trace("UPDATE MoreLike_Base  SET Batch = -1..." );
		$mySql->query("UPDATE MoreLike_Base  SET Batch = -1 WHERE Property_id IN (SELECT Property_id FROM Real_Listing WHERE Status NOT IN ('A','U'))");
		
		$mySql->query("UPDATE MoreLike_Base mb, MoreLike_Listings ml SET mb.Batch = 1 WHERE mb.Property_id = ml.Property_id AND ml.qProp_id IN 
		(SELECT Property_id FROM Real_Listing  WHERE Status NOT IN ('A','U')) AND mb.Batch = 0");

		trace("Insert all the records in the MoreLike_Base table for calculation as base properties." );
		$mySql->query("INSERT INTO MoreLike_Base( Property_id, MLS, PropertyType, ListPrice, SquareFeet, Bedrooms, Bathrooms)
		(SELECT r.Property_id, r.ListingNumber AS MLS, r.PropertyType, r.ListPrice, r.SquareFeet, r.TotalBedrooms AS Bedrooms, r.TotalBathrooms AS Bathrooms
		FROM Real_Listing r, Real_Location l
		WHERE r.Location_id = l.Location_id AND r.Status IN ('A', 'U') AND l.Active =1 AND r.Property_id NOT IN 
		(SELECT Property_id FROM MoreLike_Base))");
		
		trace("Delete all the old data from MoreLike_Listings, MoreLike_Base and MoreLike_QWords table..." );
		$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id IN (SELECT Property_id FROM MoreLike_Base WHERE Batch<>0)");
		$mySql->query("DELETE FROM MoreLike_Base WHERE Batch = -1");
		$mySql->query("DELETE FROM MoreLike_QWords WHERE Like_id NOT IN (SELECT Like_id FROM MoreLike_Listings)");

		//Use the following to get MAX_LISTINGS (or MIN_LISTINGS)
		$max_listing = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = \''.MORELIKE.'\' AND `subKeyname` = \''.MAX_LISTINGS.'\'',"KeyValue"); 
		
		//Fetch the proxity or distance limit for search same properties
		$max_proximity = $mySql->selectcell('SystemSettings', "WHERE Keyname = 'MORELIKE' AND subKeyname = 'PROXIMITY'","KeyValue");
	
		// Gather the Property Listings for processing
		trace("Gather the Property Listings for processing from MoreLike_Base...");
		$mySql->query("SELECT * FROM MoreLike_Base WHERE Batch=1 ORDER BY Property_id ASC " );
		$numRecords = $mySql->getRecordsCount();
		

		// FindMore Step  Executes on each Property id 
		trace("FindMore Step  Executes on each Property id" );
		if ($mySql->getRecordsCount()> 0)
		{
			$mySql->fetchAllRecords($Properties_array, MYSQL_ASSOC);
			//echo "<pre>"; print_r($Properties_array); exit;
			foreach($Properties_array as $property)
			{
				//echo '<pre>'; print_r($property);
				$Property_id= $property['Property_id'];
				$MLS= $property['MLS'];
				$PropertyType= $property['PropertyType'];

				$ListPrice= $property['ListPrice'];
				$SquareFeet= $property['SquareFeet'];
				$Bedrooms= $property['Bedrooms'];
				$Bathrooms= $property['Bathrooms'];

				//Step  1 : Delete all previous data from MoreLike_Listings & MoreLike_QWords tables
				trace("Step 1 : Delete all previous data from MoreLike_Listings & MoreLike_QWords tables" );
				
				$mySql->query("DELETE FROM MoreLike_QWords WHERE Like_id IN (SELECT Like_id FROM MoreLike_Listings
				WHERE Property_id = ".$Property_id.")");
				
				$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ".$Property_id);
			
				//Step 2 :Use the MIN/MAX fields to select matching listings
				trace("Step 2 : Use the MIN/MAX fields to select matching listings so calculate the min max field value" );

				if($SquareFeet>0)
					$BasePricePerFoot=round($ListPrice/$SquareFeet);
				else 
					$BasePricePerFoot=$ListPrice;

				// Update the PricePerFoot entries for base property in MoreLike_Base Table...
				$mySql->query("UPDATE MoreLike_Base SET  PricePerFoot= " .$BasePricePerFoot ." WHERE Property_id = ". $Property_id);
				
				//Calculate Range
				$MinPrice				= round($ListPrice -($ListPrice*0.20));
				$MaxPrice				= round($ListPrice + ($ListPrice*0.20));
				$MinPricePerFt		= round($BasePricePerFoot - ($BasePricePerFoot*0.15));
				$MaxPricePerFt	= round($BasePricePerFoot + ($BasePricePerFoot*0.15));
				$MinSqft					= round($SquareFeet - ($SquareFeet*0.20));
				$MaxSqft				= round($SquareFeet + ($SquareFeet*0.20));
				$MinBed				= $Bedrooms-1;
				$MaxBed				= $Bedrooms+1;
				$MinBath				= $Bathrooms-1;
				$MaxBath				= $Bathrooms+1;

				// fetch all the matching properties
				trace("fetch all the matching properties and  insert in to MoreLike_Listings...");
				$mySql->query("INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$Property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath.")"); 

				//echo "INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$Property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath.")"; exit;

		
				
				/*********************************************Base Property Data************************************************/
				
				trace("fetch Base property data from MoreLike_Listings for matching...");
				$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id= ".$Property_id. " AND qProp_id= ".$Property_id);
				if($mySql->getRecordsCount())
				{
					$BaseMoreLike= $mySql->fetchArray(MYSQL_ASSOC);
					//echo "<pre>"; print_r($BaseMoreLike); 
				
					$Base_Geodna			= $BaseMoreLike['geodna'];
					$geodna_length		=	strlen($Base_Geodna);
					$lat1							= $BaseMoreLike['Latitude'];
					$lon1							= $BaseMoreLike['Longitude'];
					$baseBedrooms		=	$BaseMoreLike['Bedrooms'];
					$baseBathrooms		=	$BaseMoreLike['Bathrooms'];
					$baseGarageSpaces=	$BaseMoreLike['GarageSpaces'];
					$BaseLike_id				=	$BaseMoreLike['Like_id'];
					$BaseValueScore		=	$BaseMoreLike['ValueScore'];
					$BaseJumpScore		=	$BaseMoreLike['JumpScore'];
					$BasePolygon_id		=	$BaseMoreLike['Polygon_id'];
					


					/*******************************Calculate garage space for base property in MoreLike_Base table.***************************/
					
					trace("Calculate garage space for base property in MoreLike_Base table...");
					$mySql->query("SELECT (AttachedSpaces + DetachedSpaces + RecVehicleSpaces) as garagespace FROM Location_Parking WHERE Location_id IN (SELECT `Location_id` FROM	`Real_Listing` WHERE `Property_id`= ".$Property_id.")");
				
					if($mySql->getRecordsCount())
					{
						$Location_Parking =  $mySql->fetchArray(MYSQL_ASSOC);
						$base_garagespaces = $Location_Parking['garagespace'];
					}
					else
					{
						$base_garagespaces=0;
					}
					
					// Update garage space  and PerfectScore in more like base table..
					$mySql->query("UPDATE MoreLike_Base SET GarageSpaces = ". $base_garagespaces." WHERE Property_id = ". $Property_id);
					//echo "<br />";
					//echo "UPDATE MoreLike_Base SET GarageSpaces = ". $base_garagespaces. " WHERE Property_id = ". $Property_id;
					//echo "<br />";

				/*****************************calculate the base property polygon data ***********************************************/
					
					trace("calculate the base property polygon data...");
					$mySql->query("SELECT AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft 
					FROM Polygon_Stats WHERE Polygon_id=".$BasePolygon_id." AND PropertyType= '".$PropertyType."'");
					
					if($mySql->getRecordsCount())
					{
						$Base_Polygon_Stats = $mySql->fetchArray(MYSQL_ASSOC);
						//echo "<pre>"; print_r($Base_Polygon_Stats); //exit;
						$Base_AveragePrice						= $Base_Polygon_Stats['AveragePrice'];
						$Base_AveragePriceSqft				= $Base_Polygon_Stats['AveragePriceSqft'];
						$Base_MedianPrice						= $Base_Polygon_Stats['MedianPrice'];
						$Base_MedianPriceSqft				= $Base_Polygon_Stats['MedianPriceSqft'];
						
						$min_Base_AveragePrice				= round($Base_AveragePrice - ($Base_AveragePrice*0.20));
						$max_Base_AveragePrice			= round($Base_AveragePrice + ($Base_AveragePrice*0.20));
						$min_Base_MedianPrice				= round($Base_MedianPrice - ($Base_MedianPrice*0.15));
						$max_Base_MedianPrice				= round($Base_MedianPrice + ($Base_MedianPrice*0.15));
						
						$min_Base_AveragePriceSqft		= round($Base_AveragePriceSqft - ($Base_AveragePriceSqft*0.15));
						$max_Base_AveragePriceSqft		= round($Base_AveragePriceSqft + ($Base_AveragePriceSqft*0.15));
						$min_Base_MedianPriceSqft		= round($Base_MedianPriceSqft - ($Base_MedianPriceSqft*0.10));
						$max_Base_MedianPriceSqft		= round($Base_MedianPriceSqft + ($Base_MedianPriceSqft*0.10));

						// Update Ploygon data in more like base table..
						$mySql->query("UPDATE MoreLike_Base SET PolyAveragePrice =". $Base_AveragePrice. ", PolyMedianPrice =".$Base_MedianPrice .", PolyAveragePriceSqft =".$Base_AveragePriceSqft. ", PolyMedianPriceSqft =".$Base_MedianPriceSqft." WHERE Property_id =". $Property_id); 
					}

					/************************************Calculation for Reduce the matched properties***********************************/
					
					trace("calculate the geodna matching limit for Reduce the matched properties ...");
					//$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id= ".$Property_id);
					//$total_listing_count = $mySql->getRecordsCount();
					
					$concat_limit=5;
					do
					{
						$mySql->query("SELECT * FROM MoreLike_Listings 
						WHERE Property_id =". $Property_id ." AND geodna LIKE concat(left('".$Base_Geodna."',".$concat_limit."),'%')");
						
						$matched_listing_count=$mySql->getRecordsCount();
						if($matched_listing_count>=$max_listing)
						{
							$concat_limit++;
						}
					}
					while($matched_listing_count >= $max_listing && $concat_limit < $geodna_length);


					/************************ delete all the records are not being match with the base property*****************************/
					
					trace("delete all the records are not being match with the base property ...");
					$mySql->query("SELECT Like_id FROM MoreLike_Listings WHERE Property_id =". $Property_id ." AND  Like_id NOT IN ( SELECT Like_id FROM MoreLike_Listings WHERE Property_id =". $Property_id ." AND geodna LIKE concat(left('".$Base_Geodna."',".$concat_limit."),'%') )");
					$mySql->fetchAllRecords($morelike_results, MYSQL_ASSOC);
					//echo "<pre>"; print_r($morelike_results);
				
					foreach($morelike_results as $morelike_result)
					{
						//echo "<pre>"; print_r($morelike_result);exit;
						$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id=".$Property_id." AND Like_id=".$morelike_result['Like_id'] );
					}

					/*****************************************Calculating proximity*************************************************/
					
					trace("Calculating proximity...");
					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =". $Property_id  ." order by  qProp_id ASC");
					if($mySql->getRecordsCount())
					{
						// Start calculation of total execution time of every property..
						$duration=0;
						$start = microtime(true); //start timer for calculate the total execution time.
						$mySql->fetchAllRecords($morelike_data, MYSQL_ASSOC);
						//echo "<pre>"; print_r($morelike_data);exit;
						
						foreach($morelike_data as $morelike_row )
						{
							$total_score=0;
							//echo "<pre>"; print_r($morelike_row); 
							$lat2 = $morelike_row['Latitude'];
							$lon2 = $morelike_row['Longitude'];
							$qProp_id = $morelike_row['qProp_id'];
							$unit='M';

							$distance=round(distance($lat1, $lon1, $lat2, $lon2, $unit)); 

							if($distance > $max_proximity)
							{
								$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id=".$Property_id." AND qProp_id=".$qProp_id);
							}
							else
							{
								$mySql->query("UPDATE MoreLike_Listings SET Proximity=".$distance." WHERE Property_id=".$Property_id." AND qProp_id=".$qProp_id);
							}
							
							/**************************************Step 4 : Match the Q-Values*************************************/

							trace ("Step 4 : Match the Q-Values ...");
							trace ("Fetch the Q-Values from Real_QWords table...");
							$mySql->query("SELECT QDNA, Token, QWords, IF(QDNA='JUMP',1,0) AS Jump FROM Real_QWords 
							WHERE Property_id =".$Property_id." ORDER BY Jump DESC" );
							
							if($mySql->getRecordsCount())
							{
								$mySql->fetchAllRecords($Q_Values, MYSQL_ASSOC);
								//echo "<pre>"; print_r($Q_Values); exit;
								
								// Locate & Insert the QDNA data by substituting the token# into the command below 
								foreach ($Q_Values as $Q_Values_row)
								{
									//echo "<pre>"; print_r($Q_Values_row);
									$Token=$Q_Values_row['Token'];
									if($Q_Values_row['Jump'] == 1)
									{
										$QDNA="t.QDNA='JUMP'";
									}
									else
									{
										$QDNA="t.QDNA<>'JUMP'"; 
									}

									//echo "<br />";
									$mySql->query("SELECT m.Like_id,q.QDNA,q.Token,q.QWords,t.Rank,t.Weight 
									FROM MoreLike_Listings m, Real_QWords q, tb_QDNA t 
									WHERE m.qProp_id = q.Property_id and q.Property_id = ".$qProp_id." and q.QDNA = t.QDNA AND ".$QDNA." AND q.Token = ".$Token);
									if($mySql->getRecordsCount())
									{
										$Listing_Values = $mySql->fetchArray(MYSQL_ASSOC);
										//echo "<pre>"; print_r($Listing_Values); 
										//echo "SELECT * FROM MoreLike_QWords WHERE Like_id=". $Listing_Values['Like_id']." AND QDNA ='". $Listing_Values['QDNA']."' AND Token=". $Listing_Values['Token']." AND QWords='". $Listing_Values['QWords']."'";

										$mySql->query("SELECT * FROM MoreLike_QWords WHERE Like_id=". $Listing_Values['Like_id']." AND QDNA ='". $Listing_Values['QDNA']."' AND Token=". $Listing_Values['Token']." AND QWords='". $Listing_Values['QWords']."'");
										
										if($mySql->getRecordsCount()==0)
										{
											$mySql->query("INSERT INTO MoreLike_QWords SET Like_id=". $Listing_Values['Like_id'].", QDNA ='". $Listing_Values['QDNA']."', Token=". $Listing_Values['Token'].", QWords='". $Listing_Values['QWords']."', Rank=". $Listing_Values['Rank'].", Weight=". $Listing_Values['Weight']);
											
											/*echo "<br />";
											echo "INSERT INTO MoreLike_QWords SET Like_id=". $Listing_Values['Like_id'].", QDNA ='". $Listing_Values['QDNA']."', Token=". $Listing_Values['Token'].", QWords='". $Listing_Values['QWords']."', Rank=". $Listing_Values['Rank'].", Weight=". $Listing_Values['Weight'];
											echo "<br />";
											echo "<br />";*/
										}
									}
								} // end of Q_Values loop
							}
								
							/********************************Garage Space calculation for matched properties**********************************/
							
							trace("Garage Space calculation for matched properties ...");
							$mySql->query("SELECT (AttachedSpaces + DetachedSpaces + RecVehicleSpaces) as garagespace FROM Location_Parking WHERE Location_id IN (SELECT `Location_id` FROM `Real_Listing` WHERE `Property_id`= ".$qProp_id.")");
							if($mySql->getRecordsCount())
							{
								$Location_Parking =  $mySql->fetchArray(MYSQL_ASSOC);
								$garagespaces = $Location_Parking['garagespace'];
							}
							else
							{
								$garagespaces=0;
							}
							
							// Update garage space in more like base table..
							$mySql->query("UPDATE MoreLike_Listings SET GarageSpaces = ". $garagespaces. " WHERE Property_id = ". $Property_id." AND qProp_id= ".$qProp_id);


							/********************************************Total the Jump Scores********************************************/
							
							trace("Update the Total the Jump Scores in MoreLike_Listings ...");
							$mySql->query("UPDATE MoreLike_Listings l,
							(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as JumpScore 
							FROM MoreLike_QWords q 
							WHERE q.QDNA = 'JUMP' and Like_id=".$morelike_row['Like_id']." group by q.Like_id, q.QDNA) as s 
							SET l.JumpScore = s.JumpScore 
							WHERE l.qProp_id =". $qProp_id);
										
							/********************************************Total the Q-Value Scores********************************************/
							
							trace("Update the Total the  Q-Value Scores in MoreLike_Listings ...");
							$mySql->query("UPDATE MoreLike_Listings l,
							(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as ValueScore 
							FROM MoreLike_QWords q 
							WHERE q.QDNA <> 'JUMP' and Like_id=".$morelike_row['Like_id']." group by q.Like_id) as s 
							SET l.ValueScore = s.ValueScore 
							WHERE l.qProp_id =". $qProp_id);
						}
					

							
						/***********************************Step 5 : Match the Price per Foot Start*************************************/
						
						trace ("Step 5 : Match the Price per Foot ...");
						trace ("Fetch the Q-Values from Real_QWords table...");
								
						$mySql->query("UPDATE MoreLike_Listings l, MoreLike_Base b 
						SET l.BasePerFt = 0 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot BETWEEN ".$MinPricePerFt." and ".$MaxPricePerFt ." AND l.Property_id=".$Property_id);
						
						$mySql->query("UPDATE MoreLike_Listings l, MoreLike_Base b 
						SET l.BasePerFt = -1 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot <= ".$MinPricePerFt." AND l.Property_id=".$Property_id);
						
						$mySql->query("UPDATE MoreLike_Listings l, MoreLike_Base b 
						SET l.BasePerFt = +1 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot >= ".$MaxPricePerFt ." AND l.Property_id=".$Property_id);

					}
					/****************************************** Step 6 : Polygon Match start ********************************************/
					
					trace("Polygon Matching start ...");
					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =".$Property_id." order by  qProp_id ASC");
					if($mySql->getRecordsCount())
					{
						$mySql->fetchAllRecords($morelikedata, MYSQL_ASSOC);
						//echo "<pre>"; print_r($morelikedata);exit;
						foreach($morelikedata as $morelikedata_row )
						{
							//echo "<pre>"; print_r($morelikedata_row);
							$total_score=$morelikedata_row['Score'];
							$mySql->query("SELECT AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft FROM Polygon_Stats WHERE Polygon_id = ".$morelikedata_row['Polygon_id']." AND PropertyType='". $PropertyType."'");
							//echo "SELECT AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft FROM Polygon_Stats WHERE Polygon_id = ".$morelikedata_row['Polygon_id']." AND PropertyType='". $PropertyType."'"; exit;

							if($mySql->getRecordsCount())
							{
								$Polygon_Stats_data = $mySql->fetchArray(MYSQL_ASSOC);
								//echo "<pre>"; print_r($Polygon_Stats_data); 
								
								$AveragePrice				=	$Polygon_Stats_data['AveragePrice'];
								$AveragePriceSqft		=	$Polygon_Stats_data['AveragePriceSqft'];
								$MedianPrice				=	$Polygon_Stats_data['MedianPrice'];
								$MedianPriceSqft		=	$Polygon_Stats_data['MedianPriceSqft'];

								//Compare base AveragePrice
								if($AveragePrice >= $min_Base_AveragePrice && $AveragePrice <= $max_Base_AveragePrice)
								{
									//SET 
									$PolyAvgPrc=0;
									$record['PolyAvgPrc'] = 2;
								}
								elseif($AveragePrice < $Base_AveragePrice)
								{
									$PolyAvgPrc=-1;
									$record['PolyAvgPrc'] = 0;
								}
								elseif($AveragePrice > $Base_AveragePrice)
								{
									$PolyAvgPrc=1;
									$record['PolyAvgPrc'] = 1;
								}
								
								//Compare base MedianPrice
								if($MedianPrice>=$min_Base_MedianPrice && $MedianPrice<=$max_Base_MedianPrice)
								{	
									$PolyMedPrc=0;
									$record['PolyMedPrc'] = 2;
								}
								elseif($MedianPrice < $Base_MedianPrice)
								{	
									$PolyMedPrc=-1;
									$record['PolyMedPrc'] = 0;
								}
								elseif($MedianPrice > $Base_MedianPrice )
								{	
									$PolyMedPrc=1;
									$record['PolyMedPrc'] = 1;
								}
								//Compare base AveragePriceSqft
								if($AveragePriceSqft>=$min_Base_AveragePriceSqft && $AveragePriceSqft<=$max_Base_AveragePriceSqft)
								{
									$PolyAvgPerft=0;
									$record['PolyAvgPerft'] = 2;
								}
								elseif($AveragePriceSqft < $Base_AveragePriceSqft)
								{
									$PolyAvgPerft=-1;
									$record['PolyAvgPerft'] = 1;
								}
								elseif($AveragePriceSqft > $Base_AveragePriceSqft)
								{
									$PolyAvgPerft=1;
									$record['PolyAvgPerft'] = -1;
								}
								
								//Compare base MedianPriceSqft
								if($MedianPriceSqft >=$min_Base_MedianPriceSqft && $MedianPriceSqft<=$max_Base_MedianPriceSqft)
								{	
									$PolyMedPerft=0;
									$record['PolyMedPerft'] = 2;
								}
								elseif($MedianPriceSqft < $Base_MedianPriceSqft)
								{	
									$PolyMedPerft=-1;
									$record['PolyMedPerft'] = 1;
								}
								elseif($MedianPriceSqft > $Base_MedianPriceSqft)
								{	
									$PolyMedPerft=1;
									$record['PolyMedPerft'] = -1;
								}
								
								$tot_score=$total_score + $record['PolyAvgPrc'] + $record['PolyMedPrc'] + $record['PolyAvgPerft'] + $record['PolyMedPerft']; 
								//echo "<pre>"; print_r($record);
								$mySql->query("UPDATE MoreLike_Listings SET PolyAvgPrc=".$PolyAvgPrc.", PolyMedPrc=".$PolyMedPrc.", PolyAvgPerft =".$PolyAvgPerft.", PolyMedPerft =".$PolyMedPerft.",Score = ".$tot_score ."  WHERE Property_id =".$Property_id." AND qProp_id=".$morelikedata_row['qProp_id']);
								
								/*echo "<br />";
								echo "UPDATE MoreLike_Listings SET PolyAvgPrc=".$PolyAvgPrc.", PolyMedPrc=".$PolyMedPrc.", PolyAvgPerft =".$PolyAvgPerft.", PolyMedPerft =".$PolyMedPerft.",Score = ".$tot_score ."  WHERE Property_id =".$Property_id." AND qProp_id=".$morelikedata_row['qProp_id'];
								echo "<br />";
								echo "UPDATE MoreLike_Listings SET PolyAvgPrc=".$record['PolyAvgPrc'].", PolyMedPrc=".$record['PolyMedPrc'].", PolyAvgPerft =".$record['PolyAvgPerft'].", PolyMedPerft =".$record['PolyMedPerft'].",Score = ".$tot_score ."  WHERE Property_id =".$Property_id." AND qProp_id=".$morelikedata_row['qProp_id'];
								echo "<br />";*/
							}
						}
					}

					/****************************************** Calculate total score ***********************************************/
						
					trace ("Step 7 : Calculate total score  ...");
					trace ("Fetch the all Values from MoreLike_Listings table and calculating total score...");
			
					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id ." ORDER BY qProp_id ASC");
					$mySql->fetchAllRecords($MoreLike_Properties_array, MYSQL_ASSOC);
					//echo "<pre>"; print_r($MoreLike_Properties_array); exit;
					// echo "hi";
					// $count=0;
					foreach($MoreLike_Properties_array as $MoreLike_Property)
					{
						$score =0;
						$result['Bedrooms']=0;
						$result['Bathrooms']=0;
						$oldscore=$MoreLike_Property['Score'];
						if($MoreLike_Property['Bedrooms']==$baseBedrooms)
						{
							$result['Bedrooms']=0;
						}
						else
						{
							if($MoreLike_Property['Bedrooms']<$baseBedrooms)
							{
								if($baseBedrooms<=4)
									$result['Bedrooms']=-3;
								if($baseBedrooms<=3)
									$result['Bedrooms']=-5;
								if($baseBedrooms<=2)
									$result['Bedrooms']=-8;
							}
							else
							{
								if($baseBedrooms==1)
									$result['Bedrooms']=8;
								if($baseBedrooms==2)
									$result['Bedrooms']=5;
								if($baseBedrooms==3)
									$result['Bedrooms']=3;
								if($baseBedrooms>3)
									$result['Bedrooms']=1;
							}
						}
									
								
						if($MoreLike_Property['Bathrooms']==$baseBathrooms)
						{
							$result['Bathrooms']=0;
						}
						else
						{
							if($MoreLike_Property['Bathrooms']<$baseBathrooms)
							{
								if($baseBathrooms<=3)
									$result['Bathrooms']=-5;
								if($baseBathrooms==2)
									$result['Bathrooms']=-10;
							}
							else
							{
								if($baseBathrooms==1)
									$result['Bathrooms']=8;
								if($baseBathrooms==2)
									$result['Bathrooms']=5;
								if($baseBathrooms>2)
									$result['Bathrooms']=2;
							}
						}

						//Calculate Garage Space score
						$garage_spaces=$MoreLike_Property['GarageSpaces'];
						if($base_garagespaces>0)
						{
							if($garage_spaces >= $base_garagespaces)
							{
								$result['GarageSpaces'] = 3;
							}
							else 
								$result['GarageSpaces'] = 0;
						}
						else
							$result['GarageSpaces'] = 0;

						$result['JumpScore']=ABS($MoreLike_Property['JumpScore']);
						$result['ValueScore']=ABS($MoreLike_Property['ValueScore']);


						//Calculate Proximity 
						$Proximity=ROUND($max_proximity/max($MoreLike_Property['Proximity'],1000),0);
						$result['Proximity']=$Proximity;
						
						// Calculate BasePerFT
						if($MoreLike_Property['BasePerFT']==0)
						{
							$result['BasePerFT']=2;
						}
						else
						{
							$result['BasePerFT']=0;
						}
			
						// Make total score
						$score = $oldscore+$result['Bedrooms'] + $result['Bathrooms'] + $result['JumpScore'] + $result['ValueScore'] + $result['Proximity'] + $result['BasePerFT'] + $result['GarageSpaces'];
						//echo "<br />";
						//echo "<pre>"; print_r($result);

						// Update total score in the MoreLike _Listing table..
						$mySql->query("UPDATE MoreLike_Listings SET Score=".$score." WHERE Property_id =".$Property_id." AND qProp_id=".$MoreLike_Property['qProp_id']);
						//echo "<br />";
						//echo "UPDATE MoreLike_Listings SET Score=".$score." WHERE Property_id =".$Property_id." AND qProp_id=".$MoreLike_Property['qProp_id'];
						
					}

					/**************************************Calculate Match Percent*************************************/
					
					trace("Calculate Match Percent for all the matching properties...");

					$mySql->query("SELECT * FROM MoreLike_Base WHERE Property_id= ".$Property_id);
					if($mySql->getRecordsCount())
					{
						$MoreLike_Base= $mySql->fetchArray(MYSQL_ASSOC);
					
						$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id);
						if($mySql->getRecordsCount())
						{	
							$mySql->fetchAllRecords($matchlikescoredata, MYSQL_ASSOC);
						
							foreach($matchlikescoredata as $score_records)
							{
								//$MatchPercent=ROUND(($score_records['Score']/$baselike_score['Score'])*100);
								$MatchPercent = Round(($score_records['Score']/$MoreLike_Base['PerfectScore'])*100);
								if($MatchPercent>100)
								{
									$MatchPercent=100;
								}


								$mySql->query("UPDATE MoreLike_Listings SET MatchPercent = ".$MatchPercent." 
								WHERE Property_id = ".$Property_id." AND qProp_id = ".$score_records['qProp_id']);
							}
						}
					}	

					/************************************* Best Match Results ****************************************/
					
					trace("calculation for Best Matching Results top  10 or 20...");
					trace("Delete all the listing have Both JumpScore and ValueScore =0");
					$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ".$Property_id." AND JumpScore = 0 AND ValueScore = 0");
					//	echo "DELETE FROM MoreLike_Listings WHERE Property_id = ".$Property_id." AND JumpScore = 0 AND ValueScore = 0";

					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id." ORDER BY `Score`DESC LIMIT 1");
					if($mySql->getRecordsCount())
					{	
						$morelikeproperties = $mySql->fetchArray(MYSQL_ASSOC);
						$heighestscore = $morelikeproperties['Score'];
						$percent25 = round(($heighestscore*25)/100);
						$percent50 = round(($heighestscore*50)/100);
						
						$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ". $Property_id ." AND Score < ". $percent25); 
					
						$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id. " ORDER BY `Score` DESC ");
						
						//echo '<pre>'; print_r($more_like_results); 
						$delete_count=0;
						
						if($mySql->getRecordsCount() > 10)
						{
							$mySql->fetchAllRecords($more_like_results, MYSQL_ASSOC);
							if($more_like_results[9]['Score'] < $percent50)
							{
								//keep only 10 records and delete all other records.
								foreach($more_like_results as $more_like_record)
								{
									if($delete_count>9)
									{
										$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ". $Property_id ." AND qProp_id = ".$more_like_record['qProp_id']); 
									}
									$delete_count++;
								}
							}
							else
							{
								//Keep 20 records and delete all other records.
								foreach($more_like_results as $more_like_record)
								{
									if($delete_count>19)
									{
										$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ". $Property_id ." AND qProp_id = ".$more_like_record['qProp_id']); 
									}
									$delete_count++;;
								}
							}
						}	
					}
				}
				else
				{
					trace("Delete all the listing have not sufficient data or no matching...");
					$mySql->query("DELETE FROM MoreLike_Listings WHERE Property_id = ". $Property_id); 
				}
			

				/***********************************MatchCount & Duration**************************************/
				
				trace("Calculate the total Matching properties Count...");
				$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id. " ORDER BY `Score` DESC ");
				$Total_Match = $mySql->getRecordsCount();
				$end = microtime(true);
				$duration=round($end - $start);
				$mySql->query("UPDATE MoreLike_Base SET Duration =". $duration.", MatchCount= ".$Total_Match." WHERE Property_id = ". $Property_id);
				//echo "<br />";
				//echo "UPDATE MoreLike_Base SET Duration = ". $duration.", MatchCount= ".$Total_Match." WHERE Property_id = ". $Property_id;



				/*************************************calculate perfectscore for base property***************************************/
				
				trace("calculate perfectscore for base property and update the base property...");
				$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id= ".$Property_id. " AND qProp_id= ".$Property_id);
				if($mySql->getRecordsCount())
				{
					$MoreLike_Listings= $mySql->fetchArray(MYSQL_ASSOC);
					//echo "<pre>"; print_r($MoreLike_Listings); 
					
					$perfectscore = $MoreLike_Listings['ValueScore'] + $MoreLike_Listings['JumpScore'];
					
					// Update  PerfectScore in more like base table..
					$mySql->query("UPDATE MoreLike_Base SET  PerfectScore= " .$perfectscore ." WHERE Property_id = ". $Property_id);
					//echo "UPDATE MoreLike_Base SET  PerfectScore= " .$perfectscore ." WHERE Property_id = ". $Property_id;
					//echo "<br />"; exit;
				}
				
				/*************************************calculate ScorePercent for match sold properties***************************************/
		
				trace("calculate ScorePercent for match sold property and update the properties Listing...");
				
				$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id ." AND qProp_id=".$Property_id);
				if($mySql->getRecordsCount())
				{
					$baselike_score=$mySql->fetchArray(MYSQL_ASSOC);
					//echo "<pre>";print_r($baselike_score); exit;
					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id= ".$Property_id);
					
					if($mySql->getRecordsCount())
					{
						$mySql->fetchAllRecords($MoreLike_Listing_array, MYSQL_ASSOC);

						foreach($MoreLike_Listing_array as $MoreLike_Listing)
						{
							//$ScorePercent = Round(($MoreLike_Listing['Score']/$MoreLike_Base['PerfectScore'])*100);
							$ScorePercent=ROUND(($MoreLike_Listing['Score']/$baselike_score['Score'])*100);
							
							// Update  ScorePercent in MoreLike_Listings table..
							trace("UPDATE MoreLike_Listings for  ScorePercent...");
							$mySql->query("UPDATE MoreLike_Listings SET  ScorePercent= " .$ScorePercent ." WHERE Property_id = ". $Property_id." AND qProp_id =".$MoreLike_Listing['qProp_id']);
						}
					}
				}

				/**********************************************************************************************************************/
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
	//function calculate distance between two properties
	function distance($lat1, $lon1, $lat2, $lon2, $unit) 
	{
		  $theta = $lon1 - $lon2;
		  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		  $dist = acos($dist);
		  $dist = rad2deg($dist);
		  $miles = $dist * 60 * 1.1515;
		  $unit = strtoupper($unit);

		 if ($unit == "K") 
		{
			return ($miles * 1.609344);
		}
		else if ($unit == "M") // for meter
		{
			return ($miles * 1609.344);
		} 
		else
		{
			return $miles;
		}
	}
?>