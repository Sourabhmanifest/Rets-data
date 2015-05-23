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
    define('PROCESS_NAME', 'FindMore Sold');
	define('PROCESS_ID', '2.14');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
	define('LOG_FILEPATH', './logs/findmore_sold'.TODAY.'.log');
    
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
		//trace("Establish database connection..." );
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();
	
		 //Write process start record
		log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED, PROCESS_ID);

		$date= date("Y-m-d",strtotime("-7 days"));
		//echo $sold_modified=Date("Y-m-d H:i:s",strtotime("-24 months")); exit;
		
		//Initialize
		trace("UPDATE MoreLike_Base  SET SoldBatch = 1..." );
		$mySql->query("UPDATE MoreLike_Base SET SoldBatch = 1 WHERE Property_id IN (SELECT Property_id FROM Analysis_Price_log  WHERE Code = 0) AND SoldBatch = 0");
		$mySql->query("UPDATE MoreLike_Base SET SoldBatch = 1 WHERE SoldBatchDate <'". $date ."' AND SoldBatch = 0");
	
	
		// Use the following to get MAX_LISTINGS (or MIN_LISTINGS)
		$max_listing = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = \''.MORELIKE.'\' AND `subKeyname` = \''.MAX_LISTINGS_SOLD.'\'',"KeyValue"); 
		$min_listing = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = \''.MORELIKE.'\' AND `subKeyname` = \''.MIN_LISTINGS_SOLD.'\'',"KeyValue"); 
		
		// fetch the no of sold month..
		$sold_months = $mySql->selectcell('SystemSettings', 'WHERE `Keyname` = \''.MORELIKE.'\' AND `subKeyname` = \''.SOLD_MONTHS.'\'',"KeyValue"); 
		$months=$sold_months." month";
		$sold_modified=Date("Y-m-d H:i:s",strtotime("-$months"));

		
		// Fetch the proxity or distance limit for search same properties
		$max_proximity = $mySql->selectcell('SystemSettings', "WHERE Keyname = 'MORELIKE' AND subKeyname = 'PROXIMITY'","KeyValue");
	
		//Gather the Property Listings for processing
		trace("Gather the Property Listings for processing from MoreLike_Base...");
		$mySql->query("SELECT * FROM MoreLike_Base WHERE Batch=1 AND Property_id IN (SELECT Property_id FROM Analysis_Price_log WHERE code=0) ORDER BY Property_id ASC " );
		$numRecords = $mySql->getRecordsCount();
		
		// FindMore Steps Executes on each Property id 
		trace("FindMore Steps Executes on each Property id" );
		if ($mySql->getRecordsCount()> 0)
		{
			$mySql->fetchAllRecords($Properties_array, MYSQL_ASSOC);
			//echo "<pre>"; print_r($Properties_array); exit;
			foreach($Properties_array as $property)
			{
				// echo '<pre>'; print_r($property);
				$Property_id		= $property['Property_id'];
				$MLS					= $property['MLS'];
				$PropertyType	= $property['PropertyType'];
				$ListPrice			= $property['ListPrice'];
				$SquareFeet		= $property['SquareFeet'];
				$Bedrooms		= $property['Bedrooms'];
				$Bathrooms		= $property['Bathrooms'];
				
				//$Property_id	=983;
				//Steps1 : Delete all previous data from MoreLike_Listings & MoreLike_QWords tables
				trace("Steps1 : Delete all previous data from MoreLike_Listings_Sold & MoreLike_QWords_Sold tables" );
				
				$mySql->query("DELETE FROM MoreLike_QWords_Sold WHERE Like_id IN (SELECT Like_id FROM MoreLike_Listings_Sold
				WHERE Property_id = ".$Property_id.")");
				
				$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ".$Property_id);
			
				/******************************changes on 13 march 2015**************************************/
				
				// Step : 2  Retrieve the Polygon_id for the base property.
				//$Location_id = $mySql->selectcell('Real_Listing', "WHERE Property_id = ".$Property_id,"Location_id");
				$mySql->query("SELECT Polygon_id FROM `Real_Polygon` WHERE Location_id IN (select Location_id from Real_Listing WHERE Property_id=".$Property_id.")");
				if($mySql->getRecordsCount())
				{
					$Base_Polygon_id= $mySql->fetchArray(MYSQL_ASSOC);
					//echo '<pre>'; print_r($Base_Polygon_id);

					// Calculate the MIN/MAX fields for base listings
					trace("Steps :2 Use the MIN/MAX fields to select matching listings so calculate the min max field value" );

					if($SquareFeet>0 && $ListPrice>0)
						$BasePricePerFoot=round($ListPrice/$SquareFeet);
					else 
						$BasePricePerFoot=0;

					// Update the PricePerFoot entries for base property in MoreLike_Base Table...
					$mySql->query("UPDATE MoreLike_Base SET  PricePerFoot= " .$BasePricePerFoot ." WHERE Property_id = ". $Property_id);
				
					//Calculate Range
					$MinPrice					= round($ListPrice - ($ListPrice*0.20));
					$MaxPrice					= round($ListPrice + ($ListPrice*0.20));
					$MinPricePerFt			= round($BasePricePerFoot - ($BasePricePerFoot*0.15));
					$MaxPricePerFt		= round($BasePricePerFoot + ($BasePricePerFoot*0.15));
					$MinSqft						= round($SquareFeet - ($SquareFeet*0.20));
					$MaxSqft					= round($SquareFeet + ($SquareFeet*0.20));
					$MinBed					= $Bedrooms-1;
					$MaxBed					= $Bedrooms+1;
					$MinBath					= $Bathrooms-1;
					$MaxBath					= $Bathrooms+1;

					/***************************************************10 April start****************************************/

					trace(" Retrieve all listings for the base listing Polygon_id and the associated Subdivision names (ie. Polygon_Subdivision table)");
					$mySql->query("SELECT s.Polygon_id, s.Sub_id, s.Subdivision, s.Count FROM Real_Listing r, Real_Polygon p, Polygon_Subdivision s where r.Location_id = p.Location_id and p.Polygon_id = s.Polygon_id  and r.Property_id =". $Property_id." order by s.Count desc");
	
					if($mySql->getRecordsCount())
					{
						$mySql->fetchAllRecords($sub_properties_array, MYSQL_ASSOC);
						// echo '<pre>'; print_r($sub_properties_array); //exit;

						$flag=0;
						$total_listing=0;
						$index=0;
						$listing_array=array();
						foreach($sub_properties_array as $sub_property_record)
						{
							// echo "<pre>"; print_r($sub_property_record);
							if($sub_property_record['Sub_id']>0)
							{
								trace("Find matching for all the listings have Sub_id>0...");
								//echo "select r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, ROUND(r.ClosePrice/r.SquareFeet,0) as PricePerFoot, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna from tb_Subdivision_City t, Polygon_Subdivision s, Real_Polygon p, Real_Location l, Real_Listing r where t.Sub_id = s.Sub_id AND s.Polygon_id = p.Polygon_id AND p.Location_id = r.Location_id AND r.Location_id = l.Location_id  AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND r.Status = 'S' AND  t.Common_id = ".$sub_property_record['Sub_id'];
								$mySql->query("select r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna from tb_Subdivision_City t, Polygon_Subdivision s, Real_Polygon p, Real_Location l, Real_Listing r where t.Sub_id = s.Sub_id AND s.Polygon_id = p.Polygon_id AND p.Location_id = r.Location_id AND r.Location_id = l.Location_id  AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND r.Status = 'S' AND  t.Common_id = ".$sub_property_record['Sub_id']);
							
								if($mySql->getRecordsCount())
								{
									$count=$mySql->getRecordsCount();
									$total_listing=$total_listing+$count;
									$mySql->fetchAllRecords($sub_properties, MYSQL_ASSOC);
									// echo "<pre>"; print_r($sub_properties); 
									if(($total_listing >= $min_listing && $total_listing <= $max_listing) ||($total_listing > $min_listing))
									{
										$flag=1;

										foreach($sub_properties as $sub_property)
										{
											$index=count($listing_array);
											$listing_array[$index]=$sub_property;
										}
										//echo "<pre>"; print_r($listing_array);  exit;
										foreach($listing_array as $listing)
										{
											$qProp_id=$listing['Property_id'];
											$MLS=$listing['MLS'];
											$Polygon_id=$listing['Polygon_id'];
											$ListPrice=$listing['ClosePrice'];
											$SquareFeet=$listing['SquareFeet'];
											$PricePerFoot=$listing['PricePerFoot'];
											$Bedrooms=$listing['Bedrooms'];
											$Bathrooms=$listing['Bathrooms'];
											$Latitude=$listing['Latitude'];
											$Longitude=$listing['Longitude'];
											$geodna=$listing['geodna'];

											if($SquareFeet!=0 && $ListPrice!=0)
											{
												$PricePerFoot= ABS(ROUND($ListPrice/$SquareFeet));
											}
											else
											{
												$PricePerFoot= 0;
											}
											
											//echo "INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude=$Latitude, Longitude=$Longitude, geodna=$geodna";
											
											trace("Insert matched properties in the MoreLike_Listings_Sold table for Sub_id>0...");
											//echo "INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'"; echo '<br />';
											//trace("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
											$mySql->query("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
										}
										break;
									}

									else
									{
										//echo "else"; 
										foreach($sub_properties as $sub_property)
										{
											$index=count($listing_array);
											$listing_array[$index]=$sub_property;
										}
										//echo "<pre>"; print_r($listing_array); 
									}
								}
							}

							if($sub_property_record['Sub_id']==0)
							{
								trace("Find matching for all the listings have Sub_id=0...");
								if($flag==0)
								{	
									//echo "select r.Property_id, r.ListingNumber AS MLS, r.ClosePrice, r.SquareFeet, ROUND(r.ClosePrice/r.SquareFeet,0) as PricePerFoot, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna from Polygon_Subdivision s, Real_Polygon p, Real_Location l, Real_Listing r WHERE s.Polygon_id = p.Polygon_id AND p.Location_id = r.Location_id AND r.Location_id = l.Location_id  AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND r.Status = 'S' AND s.Subdivision = '". $sub_property_record['Subdivision']."' AND l.geodna!=''";echo '<br />';
									$mySql->query("select r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna from Polygon_Subdivision s, Real_Polygon p, Real_Location l, Real_Listing r WHERE s.Polygon_id = p.Polygon_id AND p.Location_id = r.Location_id AND r.Location_id = l.Location_id  AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND r.Status = 'S' AND s.Subdivision = '". $sub_property_record['Subdivision']."' AND l.geodna!=''");

									if($mySql->getRecordsCount())
									{
										$count=$mySql->getRecordsCount();
										$mySql->fetchAllRecords($zerosubid_properties, MYSQL_ASSOC);
										// echo "<pre>"; print_r($zerosubid_properties);// exit;
										
										$total_listing=$total_listing+$count;
										
										//echo "<pre>"; print_r($listing_array); 

										if(($total_listing >= $min_listing && $total_listing <= $max_listing) ||($total_listing > $min_listing))
										{
											$flag=1;
											foreach($zerosubid_properties as $zerosubid_property)
											{
												$index=count($listing_array);
												$listing_array[$index]=$zerosubid_property;
											}
											foreach($listing_array as $listing)
											{
												$qProp_id=$listing['Property_id'];
												$MLS=$listing['MLS'];
												$Polygon_id=$listing['Polygon_id'];
												$ListPrice=$listing['ClosePrice'];
												$SquareFeet=$listing['SquareFeet'];
												$PricePerFoot=$listing['PricePerFoot'];
												$Bedrooms=$listing['Bedrooms'];
												$Bathrooms=$listing['Bathrooms'];
												$Latitude=$listing['Latitude'];
												$Longitude=$listing['Longitude'];
												$geodna=$listing['geodna'];
												
												if($SquareFeet!=0 && $ListPrice!=0)
												{
													$PricePerFoot= ABS(ROUND($ListPrice/$SquareFeet));
												}
												else
												{
													$PricePerFoot= 0;
												}
												
												trace("Insert matched properties in the MoreLike_Listings_Sold table for Sub_id=0...");
												//echo "INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'"; echo '<br />';
												//trace("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
											
												$mySql->query("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
											}
											break;
										}
										else
										{
											foreach($zerosubid_properties as $zerosubid_property)
											{
												$index=count($listing_array);
												$listing_array[$index]=$zerosubid_property;
											}
											//echo "<pre>"; print_r($listing_array); 
										}
									}
								}
							}
						}
					}
					if($flag==0)
					{
						trace("Find matching properties by the Geodna field...");
						$mySql->query("SELECT c.geoDNA, s.AveragePrice FROM Real_Listing r, Real_Polygon p, Polygon_Center c, Polygon_Stats s WHERE r.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id AND c.Polygon_id = s.Polygon_id AND r.Property_id = ".$Property_id);

						if($mySql->getRecordsCount())
						{
							$geodna_property=$mySql->fetchArray(MYSQL_ASSOC);
							//echo "<pre>"; print_r($geodna_property); exit;
							$geoDNA						=$geodna_property['geoDNA'];
							$geodna_length			=	strlen($geoDNA);
							$AveragePrice				=$geodna_property['AveragePrice'];
							$MinAveragePrice		= round($AveragePrice - ($AveragePrice*0.25));
							$MaxAveragePrice		= round($AveragePrice + ($AveragePrice*0.25));
							
							$concat_limit=11;
							do
							{
								//echo "SELECT r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, ROUND(r.ClosePrice/r.SquareFeet,0) as PricePerFoot, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing r, Real_Location l, tb_City t, Real_Polygon p, Polygon_Center c, Polygon_Stats s where r.Location_id = l.Location_id AND l.City_id = t.City_id AND t.Include AND l.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id AND p.Polygon_id = s.Polygon_id AND c.geoDNA LIKE concat(left('".$geoDNA."',".$concat_limit."),'%') AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND s.AveragePrice BETWEEN ".$MinAveragePrice ." AND ".$MaxAveragePrice;
								//echo '<br />';

								$mySql->query("SELECT r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing r, Real_Location l, tb_City t, Real_Polygon p, Polygon_Center c, Polygon_Stats s where r.Location_id = l.Location_id AND l.City_id = t.City_id AND t.Include AND l.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id AND p.Polygon_id = s.Polygon_id AND c.geoDNA LIKE concat(left('".$geoDNA."',".$concat_limit."),'%') AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND s.AveragePrice BETWEEN ".$MinAveragePrice ." AND ".$MaxAveragePrice);
								
								$matched_listing_count=$mySql->getRecordsCount(); 
								/*if($matched_listing_count > $max_listing)
								{
									$concat_limit++;
									//$concat++;
								}*/
								if($matched_listing_count < $min_listing)
								{
									$concat_limit--;
								}
							}
							//while($matched_listing_count > $max_listing && $matched_listing_count <$min_listing && $concat_limit < $geodna_length);
							while($matched_listing_count <$min_listing && $concat_limit < $geodna_length && $concat_limit >4);
							
							if($matched_listing_count > $min_listing)
							{ 
								trace("Insert matched properties in the MoreLike_Listings_Sold table by Geodna...");
								//echo "SELECT $Property_id, r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, ROUND(r.ClosePrice/r.SquareFeet,0) as PricePerFoot, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing r, Real_Location l, tb_City t, Real_Polygon p, Polygon_Center c, Polygon_Stats s where r.Location_id = l.Location_id AND l.City_id = t.City_id AND t.Include AND l.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id AND p.Polygon_id = s.Polygon_id AND c.geoDNA LIKE concat(left('".$geoDNA."',".$concat_limit."),'%') AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND s.AveragePrice BETWEEN ".$MinAveragePrice ." AND ".$MaxAveragePrice;
								$mySql->query("SELECT $Property_id, r.Property_id, r.ListingNumber AS MLS, p.Polygon_id, r.ClosePrice, r.SquareFeet, r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing r, Real_Location l, tb_City t, Real_Polygon p, Polygon_Center c, Polygon_Stats s where r.Location_id = l.Location_id AND l.City_id = t.City_id AND t.Include AND l.Location_id = p.Location_id AND p.Polygon_id = c.Polygon_id AND p.Polygon_id = s.Polygon_id AND c.geoDNA LIKE concat(left('".$geoDNA."',".$concat_limit."),'%') AND r.PropertyType ='". $PropertyType."' AND r.ClosePrice BETWEEN ".$MinPrice ." AND ".$MaxPrice." AND r.CloseDate > '". $sold_modified."' AND s.AveragePrice BETWEEN ".$MinAveragePrice ." AND ".$MaxAveragePrice);

								if($mySql->getRecordsCount())
								{
									$mySql->fetchAllRecords($insertingproperties, MYSQL_ASSOC);
									//echo "<pre>"; print_r($insertingproperties);
							
									foreach($insertingproperties as $row)
									{
										$qProp_id=$row['Property_id'];
										$MLS=$row['MLS'];
										$Polygon_id=$row['Polygon_id'];
										$ListPrice=$row['ClosePrice'];
										$SquareFeet=$row['SquareFeet'];
										$PricePerFoot=$row['PricePerFoot'];
										$Bedrooms=$row['Bedrooms'];
										$Bathrooms=$row['Bathrooms'];
										$Latitude=$row['Latitude'];
										$Longitude=$row['Longitude'];
										$geodna=$row['geodna'];
										
										if($SquareFeet!=0 && $ListPrice!=0)
										{
											$PricePerFoot= ABS(ROUND($ListPrice/$SquareFeet));
										}
										else
										{
											$PricePerFoot= 0;
										}
										//echo "INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'"; echo '<br />';
										//trace("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
										$mySql->query("INSERT INTO MoreLike_Listings_Sold set Property_id=$Property_id, qProp_id=$qProp_id, MLS=$MLS, Polygon_id=$Polygon_id, ListPrice=$ListPrice, SquareFeet=$SquareFeet, PricePerFoot=$PricePerFoot, Bedrooms=$Bedrooms, Bathrooms=$Bathrooms, Latitude='$Latitude', Longitude='$Longitude', geodna='$geodna'");
									}
								}
							}
						}
					}

				/***********************************************10 april ********************************************/
				/*****************************************Calculating proximity*************************************************/
					
					trace("Calculating proximity...");
					$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =". $Property_id  ." order by  qProp_id ASC");
					// echo $mySql->getRecordsCount();exit;
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
							$lat2			= $morelike_row['Latitude'];
							$lon2			= $morelike_row['Longitude'];
							$qProp_id	= $morelike_row['qProp_id'];
							$unit			='M';

							$distance=round(distance($lat1, $lon1, $lat2, $lon2, $unit));
							
							$mySql->query("UPDATE MoreLike_Listings_Sold SET Proximity=".$distance." WHERE Property_id=".$Property_id." AND qProp_id=".$qProp_id);
							/*if($distance > $max_proximity)
							{
								$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id=".$Property_id." AND qProp_id=".$qProp_id);
							}
							else
							{
								$mySql->query("UPDATE MoreLike_Listings_Sold SET Proximity=".$distance." WHERE Property_id=".$Property_id." AND qProp_id=".$qProp_id);
							}
							*/
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
									FROM MoreLike_Listings_Sold m, Real_QWords q, tb_QDNA t 
									WHERE m.qProp_id = q.Property_id and q.Property_id = ".$qProp_id." and q.QDNA = t.QDNA AND ".$QDNA." AND q.Token = ".$Token);
									if($mySql->getRecordsCount())
									{
										$Listing_Values = $mySql->fetchArray(MYSQL_ASSOC);
										//echo "<pre>"; print_r($Listing_Values); 
										//echo "SELECT * FROM MoreLike_QWords WHERE Like_id=". $Listing_Values['Like_id']." AND QDNA ='". $Listing_Values['QDNA']."' AND Token=". $Listing_Values['Token']." AND QWords='". $Listing_Values['QWords']."'";

										$mySql->query("SELECT * FROM MoreLike_QWords_Sold WHERE Like_id=". $Listing_Values['Like_id']." AND QDNA ='". $Listing_Values['QDNA']."' AND Token=". $Listing_Values['Token']." AND QWords='". $Listing_Values['QWords']."'");
										
										if($mySql->getRecordsCount()==0)
										{
											$mySql->query("INSERT INTO MoreLike_QWords_Sold SET Like_id=". $Listing_Values['Like_id'].", QDNA ='". $Listing_Values['QDNA']."', Token=". $Listing_Values['Token'].", QWords='". $Listing_Values['QWords']."', Rank=". $Listing_Values['Rank'].", Weight=". $Listing_Values['Weight']);
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
							$mySql->query("UPDATE MoreLike_Listings_Sold SET GarageSpaces = ". $garagespaces. " WHERE Property_id = ". $Property_id." AND qProp_id= ".$qProp_id);


							/********************************************Total the Jump Scores********************************************/
							
							trace("Update the Total the Jump Scores in MoreLike_Listings_Sold ...");
							$mySql->query("UPDATE MoreLike_Listings_Sold l,
							(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as JumpScore 
							FROM MoreLike_QWords_Sold q 
							WHERE q.QDNA = 'JUMP' and Like_id=".$morelike_row['Like_id']."
							group by q.Like_id, q.QDNA) as s 
							SET l.JumpScore = s.JumpScore 
							WHERE l.qProp_id =". $qProp_id);
										
							/********************************************Total the Q-Value Scores********************************************/
							
							trace("Update the Total the  Q-Value Scores in MoreLike_Listings_Sold ...");
							$mySql->query("UPDATE MoreLike_Listings_Sold l,
							(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as ValueScore 
							FROM MoreLike_QWords_Sold q 
							WHERE q.QDNA <> 'JUMP' and Like_id=".$morelike_row['Like_id']." group by q.Like_id) as s 
							SET l.ValueScore = s.ValueScore 
							WHERE l.qProp_id =". $qProp_id);
						}
					
						/***********************************Step 5 : Match the Price per Foot Start*************************************/
						
						trace ("Step 5 : Match the Price per Foot ...");
						trace ("Fetch the Q-Values from Real_QWords table...");
								
						$mySql->query("UPDATE MoreLike_Listings_Sold l, MoreLike_Base b 
						SET l.BasePerFt = 0 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot BETWEEN ".$MinPricePerFt." and ".$MaxPricePerFt ." AND l.Property_id=".$Property_id);
						
						$mySql->query("UPDATE MoreLike_Listings_Sold l, MoreLike_Base b 
						SET l.BasePerFt = -1 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot <= ".$MinPricePerFt." AND l.Property_id=".$Property_id);
						
						$mySql->query("UPDATE MoreLike_Listings_Sold l, MoreLike_Base b 
						SET l.BasePerFt = +1 
						WHERE b.Property_id = l.Property_id AND l.PricePerFoot >= ".$MaxPricePerFt ." AND l.Property_id=".$Property_id);

					}

					/************************************************************************************************************/

					$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =".$Property_id." order by  Proximity ASC");
					if($mySql->getRecordsCount())
					{
						$result_count=$mySql->getRecordsCount();
						if($result_count > $max_listing)
						{
							$delete_count=0;
							$mySql->fetchAllRecords($morelikeresult, MYSQL_ASSOC);
							//echo "<pre>"; print_r($morelikedata);exit;
							foreach($morelikeresult as $morelikedata_row )
							{
								$delete_count++;
								if($delete_count > $max_listing)
								{
									$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ". $Property_id ." AND qProp_id = ".$morelikedata_row['qProp_id']); 
								}
							}
						}
					}

			
					/****************************************** Step 6 : Polygon Match Start ********************************************/
					
					trace("Polygon Matching start ...");
					$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =".$Property_id." order by  qProp_id ASC");
					if($mySql->getRecordsCount())
					{
						$mySql->fetchAllRecords($morelikedata, MYSQL_ASSOC);
						//echo "<pre>"; print_r($morelikedata);exit;
						foreach($morelikedata as $morelikedata_row )
						{
							//echo "<pre>"; print_r($morelikedata_row);
							$total_score=$morelikedata_row['Score'];
							$mySql->query("SELECT AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft FROM Polygon_Stats WHERE Polygon_id = ".$morelikedata_row['Polygon_id']." AND PropertyType='". $PropertyType."'");

							if($mySql->getRecordsCount())
							{
								$Polygon_Stats_data = $mySql->fetchArray(MYSQL_ASSOC);
								//echo "<pre>"; print_r($Polygon_Stats_data); 
								
								$AveragePrice			=	$Polygon_Stats_data['AveragePrice'];
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
								$mySql->query("UPDATE MoreLike_Listings_Sold SET PolyAvgPrc=".$PolyAvgPrc.", PolyMedPrc=".$PolyMedPrc.", PolyAvgPerft =".$PolyAvgPerft.", PolyMedPerft =".$PolyMedPerft.",Score = ".$tot_score ."  WHERE Property_id =".$Property_id." AND qProp_id=".$morelikedata_row['qProp_id']);
							}
						}
					}

					/****************************************** Calculate total score ***********************************************/
						
					trace ("Step 7 : Calculate total score  ...");
					trace ("Fetch the all Values from MoreLike_Listings_Sold table and calculating total score...");
			
					$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =  ".$Property_id ." ORDER BY qProp_id ASC");
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
						$mySql->query("UPDATE MoreLike_Listings_Sold SET Score=".$score." WHERE Property_id =".$Property_id." AND qProp_id=".$MoreLike_Property['qProp_id']);
						
					}

					/************************************Calculate Match Percent**********************************/
					
					trace("Calculate Match Percent for all the matching properties...");
					// echo "SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id ." AND qProp_id=".$Property_id;
					$mySql->query("SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$Property_id ." AND qProp_id=".$Property_id);
					if($mySql->getRecordsCount())
					{
						$baselike_score=$mySql->fetchArray(MYSQL_ASSOC);
						//echo "<pre>";print_r($baselike_score); exit;
					
						$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =  ".$Property_id);
						if($mySql->getRecordsCount())
						{	
							$mySql->fetchAllRecords($matchlikescoredata, MYSQL_ASSOC);
							
							foreach($matchlikescoredata as $score_records)
							{
								$MatchPercent=ROUND(($score_records['Score']/$baselike_score['Score'])*100);

								$mySql->query("UPDATE MoreLike_Listings_Sold SET MatchPercent = ".$MatchPercent." 
								WHERE Property_id = ".$Property_id." AND qProp_id = ".$score_records['qProp_id']);
							}
						}
					}
					/************************************* Best Match Results ****************************************/
					
					//trace("Delete all the listing have Both JumpScore and ValueScore =0");
					//$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ".$Property_id." AND JumpScore = 0 AND ValueScore = 0");
					//	echo "DELETE FROM MoreLike_Listings WHERE Property_id = ".$Property_id." AND JumpScore = 0 AND ValueScore = 0";

					/*	$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =  ".$Property_id." ORDER BY `Score`DESC LIMIT 1");
					if($mySql->getRecordsCount())
					{	
						$morelikeproperties = $mySql->fetchArray(MYSQL_ASSOC);
						$heighestscore = $morelikeproperties['Score'];
						$percent25 = round(($heighestscore*25)/100);
						$percent50 = round(($heighestscore*50)/100);
						
						$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ". $Property_id ." AND Score < ". $percent25); 
					
						$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =  ".$Property_id. " ORDER BY `Score` DESC ");
						
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
										$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ". $Property_id ." AND qProp_id = ".$more_like_record['qProp_id']); 
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
										$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ". $Property_id ." AND qProp_id = ".$more_like_record['qProp_id']); 
									}
									$delete_count++;;
								}
							}
						}	
					}*/
				}
				else
				{
					trace("Delete all the listing have not sufficient data or no matching...");
					$mySql->query("DELETE FROM MoreLike_Listings_Sold WHERE Property_id = ". $Property_id); 
				}

				/***********************************MatchCount & Duration**************************************/
				
				trace("Calculate the total Matching properties Count...");
				$mySql->query("SELECT * FROM MoreLike_Listings_Sold WHERE Property_id =  ".$Property_id. " ORDER BY `Score` DESC ");
				$SoldBatchCount = $mySql->getRecordsCount();
				//$mySql->query("UPDATE MoreLike_Base SET SoldBatchCount= ".$SoldBatchCount." WHERE Property_id = ". $Property_id);
				//echo "<br />";
				//echo "UPDATE MoreLike_Base SET Duration = ". $duration.", MatchCount= ".$Total_Match." WHERE Property_id = ". $Property_id;
				$today=date('Y-m-d');
				$mySql->query("UPDATE MoreLike_Base SET SoldBatchCount= ".$SoldBatchCount.", SoldBatch = 0, SoldBatchDate= '".$today."', SoldBatchCount =" .$SoldBatchCount." WHERE Property_id = ". $Property_id);

				//echo "UPDATE MoreLike_Base SET SoldBatchCount= ".$SoldBatchCount." SoldBatch = 0, SoldBatchDate= '".$today."', SoldBatchCount =" .$SoldBatchCount." WHERE Property_id = ". $Property_id; 

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