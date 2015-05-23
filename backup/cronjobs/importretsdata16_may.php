#!/usr/bin/php
<?php
    //exit("in");
    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    
    
    require('retsconfig-production.php');
    require('phrets.php');
    require('mysqlconfig-production.php');
    require('mysqlhandler-2.0.php');
	require('process_status_log_insert.php');
    
    
    $realLocationKeymap = require('real_location_rets_keymap.php');
    $realOfficeKeymap = require('real_office_rets_keymap.php');
    $realAgentKeymap = require('real_agent_rets_keymap.php');
    $realListingKeymap = require('real_listing_rets_keymap.php');
    $realListingUpdateKeymap = require('real_listing_update_rets_keymap.php');
    $realFeaturesKeys = require('real_features_keys.php');
    
    
    // Settings
    define('TODAY', date('Y-m-d-H-i-s'));
    define('DEBUG', true);
    define('DEBUG_PAGES_LIMIT', 0); // 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('LOG_FILEPATH', './logs/importretsdata_'.TODAY.'.log');
    define('END_OF_LINE', "\r\n");
	define('PROCESS_ID', '1.11');
    define('PROCESS_NAME', 'Import - RETS');
    define('ENSURE_DOWNLOAD_EXECUTED', true);
    define('DOWNLOAD_PROCESS_NAME', 'Download-RETS');
    define('DOWNLOAD_PROCESS_ID', '1.10');
    define('LOG_PROCESS_STATUS', true);
    define('SOURCE_TABLE_NAME', 'temp_RETS_Import');
    define('ERROR_LOG_TABLE_NAME', 'Import_Error_Log_RETS');
    define('PAGE_SIZE', 10000);
    define('LAT_LONG_APPROXIMATION', true);
    define('IGNORE_MLSNUMBER', false);
    
    // Process status step
    define('PSS_MAIN', 0);
    
    // Process status
    define('PS_STARTED', 0);
    define('PS_COMPLETED_SUCCESS', 1);
    define('PS_COMPLETED_ERROR', -1);
    
    // Error codes
    define('ERROR_MISSING_ADDRESS', '1:0001');
    define('ERROR_MISSING_LATLONG', '1:0002');
    define('ERROR_ADDRESS_MISMATCH', '1:0004');
    define('ERROR_DUPLICATE_LATLONG', '1:0005');
    define('ERROR_UNITNUMBER_BUILDINGNUMBER_REQUIRED', '1:0006');
    
    
    class BreakException extends Exception {}
    class ImportException extends Exception {}
    class ImportErrorException extends Exception
    {
        public $sourceErrorCode;
        public $logErrorCode;
        public $logErrorMessage;
        public function __construct($_sourceErrorCode, $_logErrorCode, $_logErrorMessage)
        {
            $this->sourceErrorCode = $_sourceErrorCode;
            $this->logErrorCode = $_logErrorCode;
            $this->logErrorMessage = $_logErrorMessage;
        }
    }
    
    
    // Create log file
    if (LOG_TO_FILE) $flog = fopen(LOG_FILEPATH, 'at') or die ('Failed to open log file (\''.LOG_FILEPATH.'\').');
    trace(PROCESS_NAME.' started...');
    if (DEBUG) trace('Debug mode is ON.');
    
    try
    {
        // Establish database connection
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();
        
       // Write process start record
        log_process_status(PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED,PROCESS_ID);

        // Execute if Task 1.60 executed
        if (ENSURE_DOWNLOAD_EXECUTED)
        {
            $query = 'SELECT `id` FROM `Process_Execution` WHERE `Process_id`=\''.DOWNLOAD_PROCESS_ID.'\' AND `Executed`>=CURDATE()';
            if ($mySql->query($query)->getRecordsCount() == 0)
                throw new BreakException('\''.DOWNLOAD_PROCESS_NAME.'\' has not been executed.');
        }
        
        // Set default complete status
        $completeStatus = PS_COMPLETED_SUCCESS;
               
        try
        {
            // Delete last downloaded processed records
            trace('Deleting previously processed records...');
            $mySql->delete(SOURCE_TABLE_NAME, 'WHERE `Processed`=1');
            trace($mySql->getAffectedRows().' records have been deleted.');
            
            
            // Delete Import_Error_Log records that are older than 3 months (based on modified date)
            trace('Deleting old Import_Error_Log records');
            $mySql->delete(ERROR_LOG_TABLE_NAME, 'WHERE `Modified` < DATE_SUB(NOW(), INTERVAL 3 MONTH)');
            trace($mySql->getAffectedRows().' records have been deleted.');
            
            
            // Extract last process execution date
            $lastProcessExecutionDate = $mySql->query('SELECT `Executed` FROM `Process_Execution`WHERE `Process`=\''.PROCESS_NAME.'\'')->fetchCellValue('Executed');
            
            
            // Process listings in the Import_Error_Log
            trace('Processing Import_Error_Log records...');
            
            trace('Step 1: Set Error flag...');
            $mySql->query('
                UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `'.ERROR_LOG_TABLE_NAME.'` `log`
                    SET `tmp`.`Error`=1
                    WHERE `tmp`.`Matrix_Unique_ID`=`log`.`ListingId`
            ');
            trace($mySql->getAffectedRows().' records have been affected.');
            
            if (is_null($lastProcessExecutionDate))
                trace('Last process execution date is NULL, skipping step 2 and step 3.');
            else
            {
                trace('Step 2: Copy address data to import source table...');
                $mySql->query('
                    UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `'.ERROR_LOG_TABLE_NAME.'` `log`
                        SET `tmp`.`Error`=-1,
                            `tmp`.`Location_id`=`log`.`Location_id`,
                            `tmp`.`StreetNumber`=`log`.`StreetNumber`,
                            `tmp`.`StreetDirPrefix_Decoded`=`log`.`StreetDirSuffix`,
                            `tmp`.`StreetSuffix_Decoded`=`log`.`StreetSuffix`,
                            `tmp`.`StreetName`=`log`.`StreetName`,
                            `tmp`.`City`=`log`.`City`,
                            `tmp`.`City_Decoded`=`log`.`City_Decoded`,
                            `tmp`.`UnitNumber`=`log`.`UnitNumber`,
                            `tmp`.`StreetBuildingNumber`=`log`.`BuildingNumber`
							WHERE `tmp`.`Matrix_Unique_ID`=`log`.`ListingId`
                            AND `log`.`Error`=\''.ERROR_MISSING_ADDRESS.'\'
                            AND `log`.`Modified`>\''.$lastProcessExecutionDate.'\'
                ');
                trace($mySql->getAffectedRows().' records have been affected.');
                
                trace('Step 3: Copy lat/long data to import source table...');
                $mySql->query('
                    UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `'.ERROR_LOG_TABLE_NAME.'` `log`
                        SET `tmp`.`Error`=-1,
                            `tmp`.`Location_id`=`log`.`Location_id`,
                            `tmp`.`Latitude`=`log`.`Latitude`,
                            `tmp`.`Longitude`=`log`.`Longitude`
                        WHERE `tmp`.`Matrix_Unique_ID`=`log`.`ListingId`
                            AND `log`.`Error`=\''.ERROR_MISSING_LATLONG.'\'
                            AND `log`.`Modified`>\''.$lastProcessExecutionDate.'\'
                ');
                trace($mySql->getAffectedRows().' records have been affected.');
            }
            
            
            // Check for missing address data
            trace('Checking for missing address data...');
            $c = $mySql->select(
                SOURCE_TABLE_NAME,
                'WHERE (`Location_id` IS NULL OR `Location_id`=0)
                    AND (`StreetNumber` IS NULL OR `StreetNumber`=\'\' OR `StreetNumber`=\'0\'
                        OR `StreetName` IS NULL OR `StreetName`=\'\'
                        OR `City` IS NULL OR `City`=\'\')'
            )->getRecordsCount();
            trace($c.' record(s) found.');
            
            if ($c)
            {
                $records = null;
                $mySql->fetchAllRecords($records, MYSQL_ASSOC);
                foreach ($records as $record)
                    register_error($record, 1, ERROR_MISSING_ADDRESS, 'Missing address data.');
                trace($c.' error(s) have been registered.');
            }
            
            
            // Check for missing lat/long
            trace('Checking for missing latitude/longitude...');
            $c = $mySql->select(
                SOURCE_TABLE_NAME,
                'WHERE `Latitude` IS NULL OR `Latitude`=0 OR `Longitude` IS NULL OR `Longitude`=0'
            )->getRecordsCount();;
            trace($c.' records found.');
            
            if ($c)
            {
                $records = null;
                $mySql->fetchAllRecords($records, MYSQL_ASSOC);
                foreach ($records as $record)
                    register_error($record, 1, ERROR_MISSING_LATLONG, 'Missing latitude/longitude.');
                trace($c.' error(s) have been registered.');
            }
            
            
            // Match IDX/MLS and IDX/RETS listings
            trace('Matching IDX/MLS and IDX/RETS listings...');
            
            // Step 1: Match ListingId
            trace('Step 1: Match ListingId...');
           
				$query='UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, Real_Listing `real`
                SET `tmp`.`Property_id`=`real`.`Property_id`,
                    `tmp`.`Location_id`=`real`.`Location_id`,
                    `tmp`.`lastMLSNumber`=`real`.`ListingNumber`,
                    `tmp`.`lastStatus`=`real`.`Status`,
                    `tmp`.`FoundID`=1
					WHERE `tmp`.`Matrix_Unique_ID`=`real`.`ListingId`
                    AND `tmp`.`Error`<1';
          // echo '<pre>'; print_r($query);exit;
			$mySql->query($query);
            trace($mySql->getAffectedRows().' matches found.');
            
            if (IGNORE_MLSNUMBER)
                trace('MLS Number is no longer used.');
            else
            {
                // Step 2: Match MLS#
                trace('Step 2: Match MLS#...');
                $mySql->query('
                    UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `Real_Listing` `real`
                    SET `tmp`.`Property_id`=`real`.`Property_id`,
                        `tmp`.`Location_id`=`real`.`Location_id`,
                        `tmp`.`lastMLSNumber`=`real`.`ListingNumber`,
                        `tmp`.`lastStatus`=`real`.`Status`,
                        `tmp`.`FoundMLSNumber`=1
						WHERE `tmp`.`FoundID`=0
                        AND `tmp`.`Error`<1
                        AND (`tmp`.`MLSNumber`=`real`.`ListingNumber` OR `tmp`.`RefreshFromMLSNumber`=`real`.`ListingNumber`)
                ');
                trace($mySql->getAffectedRows().' matches found.');
                
                // Step 3: Confirm address match
                trace('Step 3: Confirm address match');
                $mySql->query('
                    UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `Real_Location` `addr`
                        SET `tmp`.`AddressMatch`=1
                    WHERE `tmp`.`FoundMLSNumber`=1
                        AND `tmp`.`Error`<1
                        AND `tmp`.`StreetNumber`=`addr`.`StreetNumber`
                        AND `tmp`.`StreetName`=`addr`.`StreetName`
                ');
                trace($mySql->getAffectedRows().' matches found.');
                
                
                // Check for address mismatches
                trace('Checking for address mismatches...');
                $c = $mySql->select(SOURCE_TABLE_NAME, 'WHERE `FoundMLSNumber`=1 AND `AddressMatch`=0')->getRecordsCount();
                trace($c.' mismatches found.');
                
                if ($c)
                {
                    unset($records);
                    $mySql->fetchAllRecords($records, MYSQL_ASSOC);
                    foreach ($records as $record)
                        register_error($record, 1, ERROR_ADDRESS_MISMATCH, 'MLS# '.$record['MLSNumber'].' '.$record['PropertyType'].' found, no address match.');
                    trace($c.' error(s) have been registered.');
                }
            }
            
            
            // Process not matched records
            trace('Processing not matched records...');
            $mySql->query('SELECT COUNT(*) AS `count` FROM `'.SOURCE_TABLE_NAME.'` WHERE `Error`=0 AND `FoundID`=0'.(IGNORE_MLSNUMBER ? '' : ' AND `FoundMLSNumber`=0').' ORDER BY `Imported`, `Import_id` ASC');
            $recordsCount = $mySql->fetchCellValue('count');
            if (DEBUG_PAGES_LIMIT  and  $recordsCount > PAGE_SIZE*DEBUG_PAGES_LIMIT)
                $recordsCount = PAGE_SIZE*DEBUG_PAGES_LIMIT;
            trace($recordsCount.' record(s) found.');
            
            if ($recordsCount)
            {
                // Counters
                $cProcessedRecords = 0;
                $cPages = 0;
                $cSuccess = 0;
                $cErrors = 0;
                $lastId = 0;
                
                do
                {
                    $cPages += 1;
                    $mySql->query('SELECT * FROM `'.SOURCE_TABLE_NAME.'` WHERE `Error`=0 AND `FoundID`=0'.(IGNORE_MLSNUMBER ? '' : ' AND `FoundMLSNumber`=0').' AND `Import_id`>'.$lastId.' ORDER BY `Imported`, `Import_id` ASC LIMIT '.PAGE_SIZE);
                    $sourceRecords = null;
                    $mySql->fetchAllRecords($sourceRecords, MYSQL_ASSOC);
					//echo '<pre>';print_r($sourceRecords);  exit;
					//echo '<pre>';print_r($sourceRecords); exit;
                    // Process record by record
                    $sourceRecordsCount = count($sourceRecords);
                    if ($sourceRecordsCount)
                    {
                        foreach ($sourceRecords as $sourceRecord)
                        {
							
							//update_real_location($sourceRecord);
							//$sourceRecord['City']=$sourceRecord['City_Decoded'];
							
                            try
                            {
                                trace('Processing record '.(++$cProcessedRecords).' of '.$recordsCount.' (Import_id='.$sourceRecord['Import_id'].'; Matrix_Unique_ID='.$sourceRecord['Matrix_Unique_ID'].'; MLSNumber='.$sourceRecord['MLSNumber'].'; PropertyType='.$sourceRecord['PropertyType'].'; PropertySubType='.$sourceRecord['PropertySubType'].'; Status='.$sourceRecord['Status'].')...');
                                if ($sourceRecord['PropertyType'] == 'LND')
                                {
                                    trace('SKIP.');
                                    continue;
                                }
                                $mySql->startTransaction();
                                
                                if ($sourceRecord['UnitNumber'] == '0')
                                    $sourceRecord['UnitNumber'] = '';
                                if ($sourceRecord['StreetBuildingNumber'] == '0')
                                    $sourceRecord['StreetBuildingNumber'] = '';
                                
                                
                                $matchFound = false;
                                $locationListing = null;
                                $realListing = null;
                                
                                
                                // Search for Real_Location by address
                                trace('Searching for Real_Location by address...');
									$criteria = 'WHERE `StreetNumber`=\''.mysql_real_escape_string($sourceRecord['StreetNumber']).'\'
                                    AND `StreetName`=\''.mysql_real_escape_string($sourceRecord['StreetName']).'\'
                                    AND `StreetSuffix`=\''.mysql_real_escape_string($sourceRecord['StreetSuffix']).'\'
                                    AND `StreetDirSuffix`=\''.mysql_real_escape_string($sourceRecord['StreetDirPrefix']).'\'
                                    AND `City_id`=\''.mysql_real_escape_string($sourceRecord['City']).'\'';
                                if ($sourceRecord['PropertyType'] == 'RES') // RES or COND
                                    $criteria .= ' AND (`PropertyType`=\'RES\' OR `PropertyType`=\'COND\')';
                               /* else
                                    $criteria .= ' AND `PropertyType`=\''.$sourceRecord['PropertyType'].'\'';*/
                                $c = $mySql->select('Real_Location', $criteria)->getRecordsCount();
                                
                                
                                if ($c == 0) // Match not found
                                {
                                    trace('Match not found.');
                                    
                                    // Search for Real_Location by partial address
                                    trace('Searching for Real_Location by partial address...');
                                    $criteria = 'WHERE `StreetNumber`=\''.mysql_real_escape_string($sourceRecord['StreetNumber']).'\'
                                        AND `StreetName`=\''.mysql_real_escape_string($sourceRecord['StreetName']).'\'
                                        AND `City_id`=\''.mysql_real_escape_string($sourceRecord['City']).'\'';
                                    
                                    if ($sourceRecord['StreetDirPrefix']=='S' or $sourceRecord['StreetDirPrefix']=='E' or $sourceRecord['StreetDirPrefix']=='W')
                                        $criteria .= ' AND `StreetDirSuffix`=\''.mysql_real_escape_string($sourceRecord['StreetDirPrefix']).'\'';
                                    if (empty($sourceRecord['StreetDirPrefix']) or $sourceRecord['StreetDirPrefix']=='N')
                                        $criteria .= ' AND (`StreetDirSuffix` IS NULL OR `StreetDirSuffix`=\'\' OR `StreetDirSuffix`=\'N\')';
                                    
                                    if ($sourceRecord['PropertyType'] == 'RES') // RES or COND
                                        $criteria .= ' AND (`PropertyType`=\'RES\' OR `PropertyType`=\'COND\')';
                                    /*else
                                        $criteria .= ' AND `PropertyType`=\''.$sourceRecord['PropertyType'].'\'';*/
                                    $c = $mySql->select('Real_Location', $criteria)->getRecordsCount();
                                }
                                
                                
                                if ($c == 0) // Match not found even by partial address
                                {
                                    trace('Match not found.');
                                    // Check for missing lat/long
                                }
                                elseif ($c == 1) // Single match found
                                {
                                    $matchFound = true;
                                    // Extract Real_Location record
                                    $realLocation = $mySql->fetchAssoc();
                                    $sourceRecord['Location_id'] = $realLocation['Location_id'];
                                    trace('Match found (Location_id='.$realLocation['Location_id'].', PropertyType='.$realLocation['PropertyType'].').');
                                    if ($sourceRecord['PropertyType'] == 'RES') // RES or COND
                                    {
                                        $sourcePropertyType = ($sourceRecord['PropertySubType'] == 'ATTSF' ? 'COND' : 'RES');
                                        if ($sourcePropertyType != $realLocation['PropertyType'])
                                            trace('WARNING: Source and found PropertyType do not match.');
										/************************2015-01-19 *****************************/
										if($sourcePropertyType=="RES" && ($sourceRecord['StructuralStyle'] == "CONDO"|| $sourceRecord['StructuralStyle'] == "TH"))
										{
											//SET Processed=1
											// Update source record
											trace('Set processed flag...');
											$mySql->update(SOURCE_TABLE_NAME, array('Processed' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
											trace($mySql->getAffectedRows(). ' rows affected.');
										}
                                    }
                                    
                                    
                                    // Extract related Location_Listing record
                                    $locationListing = get_location_listing_by_location($sourceRecord);
                                    
                                    
                                    // Extract related Real_Listing
                                    $realListing = get_real_listing_by_property_id($locationListing['Property_id'], $sourceRecord);
                                    $sourceRecord['Property_id'] = $realListing['Property_id'];
                                    
                                    
                                    if (!empty($sourceRecord['UnitNumber']) or !empty($sourceRecord['StreetBuildingNumber']))
                                    {
                                        trace('Checking for LocationListing.UnitNumber and .BuildingNumber match...');
                                        if ((empty($sourceRecord['UnitNumber'])  or  $sourceRecord['UnitNumber'] == $locationListing['UnitNumber']) and
                                            (empty($sourceRecord['StreetBuildingNumber'])  or  $sourceRecord['StreetBuildingNumber'] == $locationListing['BuildingNumber'])
                                        )
                                            trace('Matched.');
                                        else
                                        {
                                            trace('Not matched.');
                                            trace('Updating Location_Listing.UnitNumber and Location_Listing.BuildingNumber...');
                                            $mySql->update(
                                                'Location_Listing',
                                                array('UnitNumber' => $sourceRecord['UnitNumber'], 'BuildingNumber' => $sourceRecord['StreetBuildingNumber']),
                                                'WHERE `Location_id`='.$locationListing['Location_id'].' AND `Property_id`='.$locationListing['Property_id']
                                            );
                                        }
                                    }
                                }
                                else // ($c > 1) Multiple matches found
                                {
                                    trace($c.' matches found.');
                                    
                                    // Extract all Real_Location records
                                    $mySql->fetchAllRecordsAsMap($realLocationMap, MYSQL_ASSOC, 'Location_id');
                                    foreach ($realLocationMap as $record)
                                        $locationIds[] = $record['Location_id'];
                                    
                                    
                                    // Narrowing selection by unit# and/or building#
                                    if (!empty($sourceRecord['UnitNumber']) or !empty($sourceRecord['StreetBuildingNumber']))
                                    {
                                        trace('Narrowing selection by unit# and/or building#...');
                                        $criteria = 'WHERE `Location_id` IN ('.implode(',', $locationIds).')';
                                        if (empty($sourceRecord['UnitNumber']))
                                            $criteria .= ' AND (`UnitNumber` IS NULL OR `UnitNumber`=\'\' OR `UnitNumber`=\'0\')';
                                        else
                                            $criteria .= ' AND `UnitNumber` = \''.mysql_real_escape_string($sourceRecord['UnitNumber']).'\'';
                                        if (empty($sourceRecord['StreetBuildingNumber']))
                                            $criteria .= ' AND (`BuildingNumber` IS NULL OR `BuildingNumber`=\'\' OR `BuildingNumber`=\'0\')';
                                        else
                                            $criteria .= ' AND `BuildingNumber` = \''.mysql_real_escape_string($sourceRecord['StreetBuildingNumber']).'\'';
                                        $c = $mySql->select('Location_Listing', $criteria)->getRecordsCount();
                                        
                                        
                                        if ($c == 0)
                                            trace('Match not found.');
                                        
                                        elseif ($c == 1)
                                        {
                                            $locationListing = $mySql->fetchAssoc();
                                            $realLocation = $realLocationMap[$locationListing['Location_id']];
                                            $matchFound = true;
                                            trace('Match found (Location_id='.$realLocation['Location_id'].', PropertyType='.$realLocation['PropertyType'].').');
                                            if ($sourceRecord['PropertyType'] == 'RES') // RES or COND
                                            {
                                                $sourcePropertyType = ($sourceRecord['PropertySubType'] == 'ATTSF' ? 'COND' : 'RES');
                                                if ($sourcePropertyType != $realLocation['PropertyType'])
                                                    trace('WARNING: Source and found PropertyType do not match.');
												/******************2015-01-19 *****************************/
												if($sourcePropertyType=="RES" && ($sourceRecord['StructuralStyle'] == "CONDO"|| $sourceRecord['StructuralStyle'] == "TH"))
												{
													 // Update source record
													trace('Set processed flag...');
													$mySql->update(SOURCE_TABLE_NAME, array('Processed' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
													trace($mySql->getAffectedRows(). ' rows affected.');
												}
                                            }
                                        }
                                        
                                        else // Multimple matches found
                                            trace($c.' matches found.');
                                    }
                                    
                                    
                                    if (!$matchFound)
                                    {
                                        // Narrow selection using lat/long
                                        trace('Narrowing selection by lat/long...');
                                        $c = $mySql->select(
                                            'Real_Location',
                                            'WHERE `Location_id` IN ('.implode(',', $locationIds).')
                                                AND `Latitude`=\''.$sourceRecord['Latitude'].'\'
                                                AND `Longitude`=\''.$sourceRecord['Longitude'].'\'
                                        ')->getRecordsCount();
                                        
                                        
                                        if (LAT_LONG_APPROXIMATION)
                                        {
                                            if ($c == 0)
                                            {
                                                trace('Match not found.');
                                                trace('Narrowing selection by approximate lat/long...');
                                                
                                                $deviation = 0.000015;
                                                $step = 0.000015;
                                                $maxDeviation = 0.000030;
                                                
                                                do
                                                {
                                                    trace('Deviation='.$deviation.'...');
                                                    $c = $mySql->select(
                                                        'Real_Location',
                                                        'WHERE `Location_id` IN ('.implode(',', $locationIds).')
                                                            AND `Latitude` BETWEEN '.($sourceRecord['Latitude']-$deviation).' AND '.($sourceRecord['Latitude']+$deviation).'
                                                            AND `Longitude` BETWEEN '.($sourceRecord['Longitude']-$deviation).' AND '.($sourceRecord['Longitude']+$deviation)
                                                    )->getRecordsCount();
                                                    $deviation += $step;
                                                }
                                                while ($c == 0  and  $deviation <= $maxDeviation);
                                            }
                                        }

                                        
                                        if ($c == 0)
                                            trace('Match not found.');
                                        
                                        elseif ($c == 1)
                                        {
                                            $realLocation = $mySql->fetchAssoc();
                                            trace('Match found (Location_id='.$realLocation['Location_id'].').');
                                            trace('Confirm address match...');
                                            
                                            // Verify Street number and name match
                                            if ($realLocation['StreetNumber'] != $sourceRecord['StreetNumber']or  $realLocation['StreetName'] != $sourceRecord['StreetName'])
                                            {
                                                if (IGNORE_MLSNUMBER)
                                                    register_error($sourceRecord, 1, ERROR_ADDRESS_MISMATCH, 'ListingId '.$sourceRecord['Matrix_Unique_ID'].' '.$sourceRecord['PropertyType'].' found, no address match.');
                                                else
                                                    register_error($sourceRecord, 1, ERROR_ADDRESS_MISMATCH, 'MLS# '.$sourceRecord['MLSNumber'].' '.$sourceRecord['PropertyType'].' found, no address match.');
                                            }
                                            else
                                            {
                                                trace('Address match confirmed.');
                                            }
											 $matchFound = true;
                                        }
                                        
                                        else // Multiple matches found
                                        {
                                            trace('Multiple matches found.');
                                            
                                            // Register duplicate lat/long error
                                            throw new ImportErrorException(1, ERROR_DUPLICATE_LATLONG, 'Duplicate Lat/Long.');
                                        }
                                    }
                                }
                                
                                
                                if ($matchFound)
                                {
                                    // Update source record
                                    $sourceRecord['Location_id'] = $locationListing['Location_id'];
                                    $sourceRecord['Property_id'] = $locationListing['Property_id'];
                                    $sourceRecord['AddressMatch'] = 1;
									$sourceRecord['Processed'] = 1;
									//echo '<pre>';print_r($sourceRecord);exit;
									update_real_location($sourceRecord);
                                }
                                
                                else // Match not found
                                {
                                    // Insert `Real_Location` record
                                    $sourceRecord['Location_id'] = add_real_location($sourceRecord);
                                    
                                    // Add new property
                                    $sourceRecord['Property_id'] = add_property($sourceRecord);
                                    
                                    // Insert `Location_Listing` record
                                    add_location_listing($sourceRecord);
                                    
                                    
                                    // Update source record
                                    $sourceRecord['Processed'] = 1;
                                    $sourceRecord['AddressMatch'] = 1;
                                    $sourceRecord['FoundID'] = 1;
                                    if (!IGNORE_MLSNUMBER) $sourceRecord['FoundMLSNumber'] = 1;
                                }
                                    
                                
                                // Update source record
                                trace('Updating source record (Location_id='.$sourceRecord['Location_id'].', Property_id='.$sourceRecord['Property_id'].', Processed='.$sourceRecord['Processed'].', AddressMatch='.$sourceRecord['AddressMatch'].', FoundID='.$sourceRecord['FoundID'].', FoundMLSNumber='.$sourceRecord['FoundMLSNumber'].')...');
                                $importId = $sourceRecord['Import_id'];
                                unset($sourceRecord['Import_id']);
                                $mySql->update(SOURCE_TABLE_NAME, $sourceRecord, 'WHERE `Import_id`='.$importId);
                                trace($mySql->getAffectedRows(). ' rows affected.');
                                
                                
                                // Processing success
                                $mySql->commit();
                                trace('Record processed successfully.');
                                $cSuccess += 1;
                            }
                            
                            catch (ImportErrorException $e)
                            {
                                $mySql->rollback();
                                register_error($sourceRecord, $e->sourceErrorCode, $e->logErrorCode, $e->logErrorMessage);
                                $cErrors += 1;
                            }
                            
                            catch (ImportException $e)
                            {
                                trace('IMPORT EXCEPTION: '.$e->getMessage());
                                if (DEBUG) trace($e->getTraceAsString());
                                $mySql->rollback();
                                $mySql->update(SOURCE_TABLE_NAME, array('Processed' => 0, 'Error' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
                                $cErrors += 1;
                            }
                            
                            catch (Exception $e)
                            {
                                trace('EXCEPTION: '.$e->getMessage());
                                if (DEBUG) trace($e->getTraceAsString());
                                $mySql->rollback();
                                $mySql->update(SOURCE_TABLE_NAME, array('Processed' => 0, 'Error' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
                                $cErrors += 1;
                            }
                        }
                        
                        $lastId = $sourceRecords[$sourceRecordsCount-1]['Import_id'];
                    }
                }
                while ($sourceRecordsCount and (!DEBUG_PAGES_LIMIT  or  $cPages < DEBUG_PAGES_LIMIT));
                
                
                trace($cSuccess.' record(s) processed successfully. '.$cErrors.' error(s).');
            }
            
            
            // Process price changed records
            trace('Processing price changed records...');
            $mySql->query('
                UPDATE `'.SOURCE_TABLE_NAME.'` `tmp`, `Real_Listing` `real`
                    SET `real`.`PreviousListPrice`=`real`.`ListPrice`,
                        `real`.`ListPrice`=`tmp`.`ListPrice`,
                        `real`.`PriceChangeDate`=NOW()
                    WHERE `tmp`.`Error`<1
                        AND `real`.`Status`!=\'S\'
                        AND `tmp`.`Processed`=0
                        AND `tmp`.`Property_id`=`real`.`Property_id`
                        AND `tmp`.`ListPrice`!=`real`.`ListPrice`
            ');
            trace($mySql->getAffectedRows().' records have been affected.');
            
            
            // Process all unprocessed yet records
            trace('Processing all remaining unprocessed records...');
            $recordsCount = $mySql->count(SOURCE_TABLE_NAME, 'WHERE `Processed`=0 AND `Error`<1');
            if (DEBUG_PAGES_LIMIT  and  $recordsCount > PAGE_SIZE*DEBUG_PAGES_LIMIT)
                $recordsCount = PAGE_SIZE*DEBUG_PAGES_LIMIT;
            trace($recordsCount.' record(s) found.');
            
            if ($recordsCount)
            {
                // Counters
                $cProcessedRecords = 0;
                $cPages = 0;
                $cSuccess = 0;
                $cErrors = 0;
                $lastId = 0;
                
                do
                {
                    $cPages += 1;
                    $mySql->query('SELECT * FROM `'.SOURCE_TABLE_NAME.'` WHERE `Processed`=0 AND `Error`<1 AND `Import_id`>'.$lastId.' ORDER BY `Imported`, `Import_id` ASC LIMIT '.PAGE_SIZE);
                    $sourceRecords = null;
                    $mySql->fetchAllRecords($sourceRecords, MYSQL_ASSOC);
                    
                    
                    // Process record by record
                    $sourceRecordsCount = count($sourceRecords);
                    if ($sourceRecordsCount)
                    {
                        foreach ($sourceRecords as $sourceRecord)
                        {
                            try
                            {
                                trace('Processing record '.(++$cProcessedRecords).' of '.$recordsCount.' (Import_id='.$sourceRecord['Import_id'].'; Matrix_Unique_ID='.$sourceRecord['Matrix_Unique_ID'].'; MLSNumber='.$sourceRecord['MLSNumber'].'; PropertyType='.$sourceRecord['PropertyType'].'; PropertySubType='.$sourceRecord['PropertySubType'].'; Status='.$sourceRecord['Status'].')...');
                                if ($sourceRecord['PropertyType'] == 'LND')
                                {
                                    trace('SKIP.');
                                    continue;
                                }
                                $mySql->startTransaction();
                                
                                 $importId = $sourceRecord['Import_id'];
                                // Ensure Location_id and Property_id are set
                                if (empty($sourceRecord['Location_id']) or empty($sourceRecord['Property_id']))
                                    throw new ImportException('Missing Location_id and/or Property_id.');
                                
                                
                                // Extract related Location_Listing and Real_Listing records
                                $locationListing = get_location_listing($sourceRecord);
                                $realListing = get_real_listing_by_property_id($locationListing['Property_id'], $sourceRecord);
                                
                                
                                // Update Real_Listing record with ListingID
                                if (is_null($realListing['ListingID'])  and  $realListing['ListingNumber'] == $sourceRecord['MLSNumber'])
                                {
                                    trace('Updating ListingID...');
                                    $mySql->update('Real_Listing', array('ListingID' => $sourceRecord['Matrix_Unique_ID']), 'WHERE `Property_id`='.$sourceRecord['Property_id']);
                                    $sourceRecord['FoundID'] = 1;
                                    $mySql->update(SOURCE_TABLE_NAME, array('FoundID' => 1), 'WHERE `Import_id`='.$importId);
                                }
                                
                                
                                // Determine statuses
                                $currentStatus = $locationListing['Status'];
                                if (isset($locationListing['Restored']))
                                    $currentStatus = $realListing['Status'];
                                $retsStatus = $sourceRecord['Status'];
                                
                                
                                // Treat status 'R' as 'A'
                                if ($currentStatus == 'R')
                                    $currentStatus = 'A';
                                if ($retsStatus == 'R')
                                    $retsStatus = 'A';
                                
                                
                                // Status dependent processing
                                trace('Status dependent processing (currentStatus='.$currentStatus.', retsStatus='.$retsStatus.')...');
                                if ($currentStatus == 'A')
                                {
                                    if ($retsStatus == 'A')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] >= $realListing['MatrixModifiedDT'])
                                        {
                                            if ($sourceRecord['StatusChangeTimestamp'] > $realListing['StatusChangeDate'])
                                            {
                                                $mySql->update('Real_Listing',
                                                    array(
                                                        'Status' => $retsStatus,
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['StatusChangeTimestamp'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                            }
                                            update_property($realListing, $sourceRecord);
                                        }
                                    }
                                    
                                    elseif ($retsStatus == 'S')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] < $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                                add_property($sourceRecord);
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update('Real_Listing',
                                                    array(
                                                        'Status' => 'S',
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                        'ClosePrice' => $sourceRecord['ClosePrice'],
                                                        'CloseDate' => $sourceRecord['CloseDate'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                            }
                                        }
                                        
                                        else
                                        {
                                            $mySql->update('Real_Listing',
                                                array(
                                                    'Status' => 'S',
                                                    'PreviousStatus' => $currentStatus,
                                                    'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                    'ClosePrice' => $sourceRecord['ClosePrice'],
                                                    'CloseDate' => $sourceRecord['CloseDate'],
                                                ),
                                                'WHERE `Property_id`='.$realListing['Property_id']
                                            );
                                            
                                            if (!isset($locationListing['Restored']))
                                            {
                                                $mySql->update('Location_Listing',
                                                    array('Status' => 'S'),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }

                                            update_property($realListing, $sourceRecord);
                                        }
                                    }
                                
                                    
                                    else // $restStatus != 'A' or 'S'
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] >= $realListing['MatrixModifiedDT'])
                                        {
                                            $mySql->update('Real_Listing', array('ListingNumber' => $sourceRecord['MLSNumber']), 'WHERE `Property_id`='.$realListing['Property_id']);
                                            if ($sourceRecord['StatusChangeTimestamp'] > $realListing['StatusChangeDate'])
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => $retsStatus,
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['StatusChangeTimestamp'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                                
                                                if (!isset($locationListing['Restored']))
                                                {
                                                    $mySql->update('Location_Listing',
                                                        array('Status' => $retsStatus),
                                                        'WHERE `Property_id`='.$locationListing['Property_id']
                                                    );
                                                }
                                                
                                                //update_property($realListing, $sourceRecord);
                                            }
                                            update_property($realListing, $sourceRecord);
                                        }
                                    }
                                }
                                
                                
                                elseif ($currentStatus == 'S')
                                {
                                    if ($retsStatus == 'A')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] > $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                            {
                                                $propertyId = add_property($sourceRecord);
                                                $mySql->update(
                                                    'Location_Listing',
                                                    array('Property_id' => $propertyId, 'Status' => 'A'),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => $retsStatus,
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['StatusChangeTimestamp'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                                
                                                $mySql->update(
                                                    'Location_Listing',
                                                    array('Status' => 'A'),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }
                                        }
                                        update_property($realListing, $sourceRecord);
                                    }
                                    
                                    elseif ($retsStatus == 'S')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] < $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                                $propertyId = add_property($sourceRecord);
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => 'S',
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                        'ClosePrice' => $sourceRecord['ClosePrice'],
                                                        'CloseDate' => $sourceRecord['CloseDate'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                            }
                                        }
                                        
                                        elseif ($realListing['Status'] == 'S' and $realListing['CloseDate'] != $sourceRecord['CloseDate'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                            {
                                                $propertyId = add_property($sourceRecord);
                                                $mySql->update(
                                                    'Location_Listing',
                                                    array('Property_id' => $propertyId, 'Status' => 'S'),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }
                                        }
                                        
                                        elseif ($realListing['Status'] != 'S')
                                        {
                                            $mySql->update(
                                                'Real_Listing',
                                                array(
                                                    'Status' => 'S',
                                                    'PreviousStatus' => $currentStatus,
                                                    'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                    'ClosePrice' => $sourceRecord['ClosePrice'],
                                                    'CloseDate' => $sourceRecord['CloseDate'],
                                                ),
                                                'WHERE `Property_id`='.$realListing['Property_id']
                                            );
                                            //update_property($realListing, $sourceRecord, false);
                                        }
                                        update_property($realListing, $sourceRecord, false);
                                    }
                                    
                                    else // $retsStatus != 'A' or 'S'
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] > $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                            {
                                                $propertyId = add_property($sourceRecord);
                                                $mySql->update(
                                                    'Location_Listing',
                                                    array('Property_id' => $propertyId, 'Status' => $retsStatus),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => 'S',
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                        'ClosePrice' => $sourceRecord['ClosePrice'],
                                                        'CloseDate' => $sourceRecord['CloseDate'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                            }
                                            update_property($realListing, $sourceRecord, false);
                                        }
                                    }
                                }
                                
                                
                                else // $currentStatus != 'A' or 'S'
                                {
                                    if ($retsStatus == 'A')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] >= $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                            {
                                                $propertyId = add_property($sourceRecord);
                                                if (!isset($locationListing['Restored']))
                                                {
                                                    $mySql->update(
                                                        'Location_Listing',
                                                        array('Property_id' => $propertyId, 'Status' => 'A'),
                                                        'WHERE `Property_id`='.$locationListing['Property_id']
                                                    );
                                                }
                                            }
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => $retsStatus,
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['StatusChangeTimestamp'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                                
                                                if (!isset($locationListing['Restored']))
                                                {
                                                    $mySql->update(
                                                        'Location_Listing',
                                                        array('Status' => 'A'),
                                                        'WHERE `Property_id`='.$locationListing['Property_id']
                                                    );
                                                }
                                            }
                                            update_property($realListing, $sourceRecord, false);
                                        }
                                    }
                                    
                                    elseif ($retsStatus == 'S')
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] < $realListing['MatrixModifiedDT'])
                                        {
                                            if (!$sourceRecord['FoundID'])
                                                add_property($sourceRecord);
                                            
                                            elseif ($realListing['Status'] != 'S')
                                            {
                                                $mySql->update(
                                                    'Real_Listing',
                                                    array(
                                                        'Status' => 'S',
                                                        'PreviousStatus' => $currentStatus,
                                                        'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                        'ClosePrice' => $sourceRecord['ClosePrice'],
                                                        'CloseDate' => $sourceRecord['CloseDate'],
                                                    ),
                                                    'WHERE `Property_id`='.$realListing['Property_id']
                                                );
                                            }
                                        }
                                        
                                        else
                                        {
                                            $mySql->update(
                                                'Real_Listing',
                                                array(
                                                    'Status' => 'S',
                                                    'PreviousStatus' => $currentStatus,
                                                    'StatusChangeDate' => $sourceRecord['CloseDate'],
                                                    'ClosePrice' => $sourceRecord['ClosePrice'],
                                                    'CloseDate' => $sourceRecord['CloseDate'],
                                                ),
                                                'WHERE `Property_id`='.$realListing['Property_id']
                                            );
                                            
                                            if (!isset($locationListing['Restored']))
                                            {
                                                $mySql->update(
                                                    'Location_Listing',
                                                    array('Status' => 'S'),
                                                    'WHERE `Property_id`='.$locationListing['Property_id']
                                                );
                                            }
                                            
                                            update_property($realListing, $sourceRecord, false);
                                        }
                                    }
                                    
                                    else // $retsStatus != 'A' or 'S'
                                    {
                                        if ($sourceRecord['MatrixModifiedDT'] > $realListing['MatrixModifiedDT'])
                                            update_property($realListing, $sourceRecord, false);
                                    }
                                }
                                
                                
                                // Update source record
                                trace('Set processed flag...');
                                $mySql->update(SOURCE_TABLE_NAME, array('Processed' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
                                trace($mySql->getAffectedRows(). ' rows affected.');
                                
                                
                                // Processing success
                                $mySql->commit();
                                trace('Record processed successfully.');
                                $cSuccess += 1;
                            }
                            
                            catch (ImportException $e)
                            {
                                trace('IMPORT EXCEPTION: '.$e->getMessage());
                                if (DEBUG) trace($e->getTraceAsString());
                                $mySql->rollback();
                                $mySql->update(SOURCE_TABLE_NAME, array('Processed' => 0, 'Error' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
                                $cErrors += 1;
                            }
                            
                            catch (Exception $e)
                            {
                                trace('EXCEPTION: '.$e->getMessage());
                                if (DEBUG) trace($e->getTraceAsString());
                                $mySql->rollback();
                                $mySql->update(SOURCE_TABLE_NAME, array('Processed' => 0, 'Error' => 1), 'WHERE `Import_id`='.$sourceRecord['Import_id']);
                                $cErrors += 1;
                            }
                        }
                        
                        
                        $lastId = $sourceRecords[$sourceRecordsCount-1]['Import_id'];
                    }
                }
                while ($sourceRecordsCount and (!DEBUG_PAGES_LIMIT  or  $cPages < DEBUG_PAGES_LIMIT));
                
                
                // Totals
                trace($cSuccess.' record(s) processed successfully. '.$cErrors.' error(s).');
            }
        }
        
        catch (MySqlQueryException $e)
        {
            trace('MYSQL QUERY EXCEPTION: '.$e->getMessage());
            if (DEBUG) trace($e->getTraceAsString());
            $completeStatus = PS_COMPLETED_ERROR;
        }
        
        
        // Log process status       if (LOG_PROCESS_STATUS) 
        log_process_status(PROCESS_NAME, PSS_MAIN, 'Process completed', $completeStatus,PROCESS_ID);
    }
    
    catch (BreakException $e)
    {
        trace('BREAK EXEPTION: '.$e->getMessage());
    }
    
    catch (MySqlConnectException $e)
    {
        trace('MYSQL CONNECT EXCEPTION: '.$e->getMessage());
    }
    
    
    $mySql->close();
    trace(PROCESS_NAME.' completed.');
	if (LOG_TO_FILE and $flog) fclose($flog);
    
    
    function date_now() { return date('Y-m-d H:i:s'); }
    
    
    function trace($_msg)
    {
        global $flog;
        $now = date('Y-m-d H:i:s');
        if (LOG_TO_CONSOLE) print($now.': '.$_msg.END_OF_LINE);
        if (LOG_TO_FILE) fwrite($flog, $now.': '.$_msg.END_OF_LINE);
    }
    
    
/*    function log_process_status($_processName, $_step, $_msg, $_status)
    {
        global $mySql;
        $mySql->insert('Process_Status_Log', array(
            'Process' => $_processName,
            'Step' => $_step,
            'Message' => $_msg,
            'Status' => $_status,
        ));
    }
   */ 
    
    function register_error($_sourceRecord, $_sourceErrorCode, $_logErrorCode, $_logErrorMessage)
    {
        global $mySql;
        //echo '<pre>'; print_r($_sourceRecord);
        trace('Registering error (Import_id='.$_sourceRecord['Import_id'].', Matrix_Unique_ID='.$_sourceRecord['Matrix_Unique_ID'].', Error='.$_logErrorCode.')...');
        $data = array(
            'ListingId' => $_sourceRecord['Matrix_Unique_ID'],
            'Error' => $_logErrorCode,
            'ErrorMessage' => $_logErrorMessage,
            'Location_id' => $_sourceRecord['Location_id'],
            'StreetNumber' => $_sourceRecord['StreetNumber'],
            'StreetDirSuffix' => $_sourceRecord['StreetDirPrefix'],
            'StreetSuffix' => $_sourceRecord['StreetSuffix'],
            'StreetName' => $_sourceRecord['StreetName'],
			'City' => $_sourceRecord['City'],
			//'City' => $_sourceRecord['City_Decoded'],
            'City_Decoded' => $_sourceRecord['City_Decoded'],
            'UnitNumber' => $_sourceRecord['UnitNumber'],
            'BuildingNumber' => $_sourceRecord['StreetBuildingNumber'],
            'Latitude' => $_sourceRecord['Latitude'],
            'Longitude' => $_sourceRecord['Longitude'],
        );
        
        
        $criteria = 'WHERE `ListingId`='.$_sourceRecord['Matrix_Unique_ID'].' AND `Error`=\''.$_logErrorCode.'\'';
        $c = $mySql->select(ERROR_LOG_TABLE_NAME, $criteria)->getRecordsCount();
        if ($c == 0)
            $mySql->insert(ERROR_LOG_TABLE_NAME, $data);
        else
            $mySql->update(ERROR_LOG_TABLE_NAME, $data, $criteria);
        
        
        $mySql->update(SOURCE_TABLE_NAME, array('Error' => $_sourceErrorCode), 'WHERE `Import_id`='.$_sourceRecord['Import_id']);
    }
    
    
    function add_real_location($_data)
    {
        global $mySql;
        global $realLocationKeymap;
        trace('Adding Real_Location record...');
        
        
        $realLocation = array();
        foreach ($realLocationKeymap as $destKey => $srcKey)
        {
            if (!array_key_exists($srcKey, $_data))
                trace('WARNING: Real_Location keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
            else
                $realLocation[$destKey] = $_data[$srcKey];
        }
        if ($_data['PropertyType'] == 'RES'  and  $_data['PropertySubType'] == 'ATTSF') // COND
            $realLocation['PropertyType'] = 'COND';
        $realLocation['Entered'] = date_now();
  /*********************************************************/      
        /*foreach($realLocation as $key => $value)
		{
				if($key!='SubdivisionName')
				{
					$insert_property[$key]=$value;
				}
		}
		$id = $mySql->insert('Real_Location', $insert_property)->getInsertId();*/
/*****************************************************************/

		/*echo "insert";
		echo '<br />';
		echo '<pre>';print_r($realLocation);*/
       $id = $mySql->insert('Real_Location', $realLocation)->getInsertId();
        trace('New location ID: '.$id);
        return $mySql->getInsertId();
    }
    
    function update_real_location($_data)
    {
		//echo '<pre>';print_r($_data); 
        global $mySql;
        global $realLocationKeymap;
        trace('updating Real_Location record...');
        
        $realLocationData = array();
		$realLocationData['Location_id'] = $_data['Location_id'];
		$realLocationData['StreetNumber'] = $_data['StreetNumber'];
		$realLocationData['StreetName'] = $_data['StreetName'];
		$realLocationData['StreetSuffix'] = $_data['StreetSuffix'];
		$realLocationData['StreetDirSuffix'] = $_data['StreetDirSuffix'];
		$realLocationData['PostalCode'] = $_data['PostalCode'];
		/**************************11 feb 2015 change*********************/
		$realLocationData['City_id'] = $_data['City'];
		$realLocationData['City'] = $_data['City_Decoded'];
		/*********************************************/
		$realLocationData['County'] = $_data['County'];
		
		$realLocationData['Latitude'] = $_data['Latitude'];
		$realLocationData['Longitude'] = $_data['Longitude'];
		$realLocationData['Verified'] = 0;
		$realLocationData['geodna'] = "";

		$realLocationData['ElementarySchool']	= $_data['ElementarySchool'];
		$realLocationData['MiddleSchool']		= $_data['MiddleSchool'];
		$realLocationData['HighSchool']			= $_data['HighSchool'];
		$realLocationData['SchoolDistrict']		= $_data['SchoolDistrict'];
		
		$realLocationData['Subdivision'] = $_data['Subdivision'];
		$realLocationData['Sub_id'] = 0;
		$realLocationData['dict_Sub'] = 0;
		/*echo "update";
		echo '<br />';
		echo '<pre>';print_r($realLocationData); */
	   $mySql->update('Real_Location', $realLocationData, 'WHERE `Location_id`='.$realLocationData['Location_id']);
        trace('Real_Location location_id - '.$realLocationData['Location_id'].' updated');
    }


    function add_property($_importData)
    {
		//echo '<pre>'; print_r($_importData);exit;
        global $mySql;
        global $realOfficeKeymap;
        global $realAgentKeymap;
        global $realListingKeymap;
        trace('Adding new property...');
        
        $officeId = 0;
        if ($_importData['ListOfficeMLSID'])
        {
            // Lookup in Real_Office table. If found, update, if NOT found add.
            trace('Lookup Real_Office (ListOfficeId='.$_importData['ListOfficeMLSID'].')...');
            $query = 'SELECT * FROM `Real_Office` WHERE `ListOfficeId` = \''.mysql_real_escape_string($_importData['ListOfficeMLSID']).'\'';
            $mySql->query($query);
            
            if ($mySql->getRecordsCount() == 0) // Real_Office not found
            {
                // Insert `Real_Office` record
                trace('Not found. Adding Real_Office record...');
                $query = 'INSERT INTO `Real_Office` SET ';
                foreach ($realOfficeKeymap as $destKey => $srcKey)
                {
                    if (!array_key_exists($srcKey, $_importData))
                        trace('WARNING: Real_Office keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
                    else
                        $query .= '`'.$destKey.'` = \''.mysql_real_escape_string($_importData[$srcKey]).'\', ';
                }
                $query .= '`Entered` = NOW()';
                $mySql->query($query);
                $officeId = $mySql->getInsertId();
                trace('New Real_Office ID: '.$officeId);
            }
            
            else // real office found
            {
                // Update `Real_Office` record
                $officeId = $mySql->fetchCellValue('Office_id');
                trace('Related Real_Office found (Office_id='.$officeId.'). Updating data...');
                $query = 'UPDATE `Real_Office` SET ';
                foreach ($realOfficeKeymap as $destKey => $srcKey)
                {
                    if (!array_key_exists($srcKey, $_importData))
                        trace('WARNING: Real_Office keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
                    else
                        $query .= '`'.$destKey.'` = \''.mysql_real_escape_string($_importData[$srcKey]).'\', ';
                }
                $query = substr($query, 0, strlen($query)-2);
                $query .= 'WHERE `Office_id` = '.$officeId;
                $mySql->query($query);
            }
        }
        $agentId = 0;
        if ($_importData['ListAgentMLSID'])
        {
            // Lookup in Real_Agent table. If found, update, if NOT found - add.
            trace('Lookup Real_Agent (ListAgentId='.$_importData['ListAgentMLSID'].')...');
            $query = 'SELECT * FROM `Real_Agent` WHERE `ListAgentId` = \''.mysql_real_escape_string($_importData['ListAgentMLSID']).'\'';
            $mySql->query($query);
            
            if ($mySql->getRecordsCount() == 0) // Real_Agent not found
            {
                // Insert `Real_Agent` record
                trace('Not found. Adding Real_Agent record...');
                $query = 'INSERT INTO `Real_Agent` SET ';
                foreach ($realAgentKeymap as $destKey => $srcKey)
                {
                    if (!array_key_exists($srcKey, $_importData))
                        trace('WARNING: Real_Agent keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
                    else
                        $query .= '`'.$destKey.'` = \''.mysql_real_escape_string($_importData[$srcKey]).'\', ';
                }
                $query .= '`Entered` = NOW()';
                $mySql->query($query);
                $agentId = $mySql->getInsertId();
                trace('New agent ID: '.$agentId);
            }
            
            else // Real_Agent found
            {
                // Update `Real_Agent` record
                $agentId = $mySql->fetchCellValue('Agent_id');
                trace('Related Real_Agent found (Agent_id='.$agentId.'). Updating data...');
                $query = 'UPDATE `Real_Agent` SET ';
                foreach ($realAgentKeymap as $destKey => $srcKey)
                {
                    if (!array_key_exists($srcKey, $_importData))
                        trace('WARNING: Real_Agent keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
                    else
                        $query .= '`'.$destKey.'` = \''.mysql_real_escape_string($_importData[$srcKey]).'\', ';
                }
                $query = substr($query, 0, strlen($query)-2);
                $query .= 'WHERE `Agent_id` = '.$agentId;
                $mySql->query($query);
            }
        }
        
        
        // Insert `Real_Listing` record
        trace('Adding Real_Listing record...');
        
        $realListing = array();
        $realListing['Location_id'] = $_importData['Location_id'];
        foreach ($realListingKeymap as $destKey => $srcKey)
        {
            if (!array_key_exists($srcKey, $_importData))
                trace('WARNING: Real Listing keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
            else
                $realListing[$destKey] = $_importData[$srcKey];
        }
		if($_importData['UnitNumber']=='' && $_importData['StreetBuildingNumber']=='')
		{
			        $realListing['FullAddress'] = $_importData['StreetNumber'].' '.$_importData['StreetName'].' '.$_importData['StreetSuffix'].' '.$_importData['StreetDirPrefix'].', '.$_importData['City_Decoded'];
		}
		elseif($_importData['UnitNumber']!='' && $_importData['StreetBuildingNumber']=='')
		{
			        $realListing['FullAddress'] = $_importData['UnitNumber'].' - '.$_importData['StreetNumber'].' '.$_importData['StreetName'].' '.$_importData['StreetSuffix'].' '.$_importData['StreetDirPrefix'].', '.$_importData['City_Decoded'];
		}
		elseif($_importData['UnitNumber']!='' && $_importData['StreetBuildingNumber']!='')
		{
			        $realListing['FullAddress'] = $_importData['StreetBuildingNumber'].' Bldg '.$_importData['UnitNumber'].' - '.$_importData['StreetNumber'].' '.$_importData['StreetName'].' '.$_importData['StreetSuffix'].' '.$_importData['StreetDirPrefix'].', '.$_importData['City_Decoded'];
		}
		/*elseif($_importData['UnitNumber']=='' && $_importData['StreetBuildingNumber']!='')
		{
			        $realListing['FullAddress'] = $_importData['StreetBuildingNumber'].' Bldg  '.$_importData['StreetNumber'].' '.$_importData['StreetName'].' '.$_importData['StreetSuffix'].' '.$_importData['StreetDirPrefix'].', '.$_importData['City_Decoded'];
		}*/
		//echo '<pre>';print_r($realListing['FullAddress']);exit;
        
		//$realListing['FullAddress'] = $_importData['StreetNumber'].' '.$_importData['StreetName'].' '.$_importData['StreetSuffix'].' '.$_importData['StreetDirPrefix'];
        if ($_importData['GarageSpacesCount'] > 0)
            $realListing['CarStorage'] = 'G';
        $realListing['ListOffice_id'] = $officeId;
        $realListing['ListAgent_id'] = $agentId;
        if ($_importData['PropertyType'] == 'RES'  and  $_importData['PropertySubType'] == 'ATTSF') // COND
            $realListing['PropertyType'] = 'COND';
        $realListing['Entered'] = date_now();
        $realListing['ListingDate'] = is_null($_importData['MatrixModifiedDT']) ? date_now() : $_importData['MatrixModifiedDT'];
        $realListing['AssociationFee'] = is_null($_importData['AssociationFeeAnnual']) ? 0 : $_importData['AssociationFeeAnnual'];
        /*******************************************************************/

		/*foreach($realListing as $key => $value)
		{
				if($key!='Style_Decoded')
				{
					$insert_property[$key]=$value;
				}
		}
		$propertyId = $mySql->insert('Real_Listing', $insert_property)->getInsertId();*/
			/*************************************************************/
		//echo '<pre>'; print_r($realListing);
        $propertyId = $mySql->insert('Real_Listing', $realListing)->getInsertId();
        trace('New property ID: '.$propertyId);
            
        
        // Insert Real_Text record
        add_real_text($propertyId, $_importData);
        return $propertyId;
    }
    
    
    function add_location_listing($_sourceRecord)
    { 
        global $mySql;
        
        trace('Adding Location_Listing record (Location_id='.$_sourceRecord['Location_id'].', Property_id='.$_sourceRecord['Property_id'].')...');
        $locationListing = array(
            'Location_id' => $_sourceRecord['Location_id'],
            'Property_id' => $_sourceRecord['Property_id'],
            'UnitNumber' => $_sourceRecord['UnitNumber'],
            'BuildingNumber' => $_sourceRecord['StreetBuildingNumber'],
            'Status' => $_sourceRecord['Status'],
            'Entered' => date('Y-m-d H:i:s'),
        );
        $mySql->insert('Location_Listing', $locationListing);
        
        return $locationListing;
    }
    
    
    function get_location_listing($_sourceRecord)
    {
        global $mySql;
        
        // Search for related Location_Listing record
        trace('Searching for related Location_Listing record (Location_id='.$_sourceRecord['Location_id'].', Property_id='.$_sourceRecord['Property_id'].')...');
        $c = $mySql->select('Location_Listing', 'WHERE `Location_id`='.$_sourceRecord['Location_id'].' AND `Property_id`='.$_sourceRecord['Property_id'])->getRecordsCount();
        
        
        if ($c == 0)
        {
            trace('Related Location_Listing record not found.');

            // Restore Location_Listing record
            $locationListing = add_location_listing($_sourceRecord);
            $locationListing['Restored'] = true;
        }
        
        elseif ($c == 1)
        {
            trace('Related Location_Listing found.');
            
            // Extract Location_Listing record
            $locationListing = $mySql->fetchAssoc();
        }
        
        else // Multiple Locatin_Listing records found
            throw new Exception('DATA ERROR: '.$c.' records found.');
        
        
        return $locationListing;
    }
    
    
    function get_location_listing_by_location(&$_sourceRecord)
    {
        global $mySql;
        
        trace('Searching for Location_Listing record by location (Import_id='.$_sourceRecord['Import_id'].', Location_id='.$_sourceRecord['Location_id'].', PropertyType='.$_sourceRecord['PropertyType'].', PropertySubType='.$_sourceRecord['PropertySubType'].', BuildingNumber='.$_sourceRecord['StreetBuildingNumber'].', UnitNumber='.$_sourceRecord['UnitNumber'].')...');

        $criteria = 'WHERE `Location_id`='.$_sourceRecord['Location_id'];
        if ($_sourceRecord['PropertyType'] == 'RES') // 'RES' or 'COND'
        {
            if (empty($_sourceRecord['StreetBuildingNumber']))
                $criteria .= ' AND (`BuildingNumber` IS NULL OR `BuildingNumber`=\'\' OR `BuildingNumber`=\'0\')';
            else
                $criteria .= ' AND `BuildingNumber` = \''.mysql_real_escape_string($_sourceRecord['StreetBuildingNumber']).'\'';
            if (empty($_sourceRecord['UnitNumber']))
                $criteria .= ' AND (`UnitNumber` IS NULL OR `UnitNumber`=\'\' OR `UnitNumber`=\'0\')';
            else
                $criteria .= ' AND `UnitNumber` = \''.mysql_real_escape_string($_sourceRecord['UnitNumber']).'\'';
        }
        $c = $mySql->select('Location_Listing', $criteria)->getRecordsCount();
        
        
        if ($c == 0)
        {
            trace('Location_Listing record not found.');
            
            
           /* if (empty($_sourceRecord['StreetBuildingNumber']) or empty($_sourceRecord['UnitNumber']))
            {
                trace('Searching for Location_Listing record by location w/o BuildingNumber/UnitNumber...');
                $criteria = 'WHERE `Location_id`='.$_sourceRecord['Location_id'];
                $c = $mySql->select('Location_Listing', $criteria)->getRecordsCount();
                trace($c.' records found.');
                if ($c)
                    throw new ImportErrorException(1, ERROR_UNITNUMBER_BUILDINGNUMBER_REQUIRED, 'UnitNumber/BuildingNumber needed for Address.');
            }*/
			if (empty($_sourceRecord['StreetBuildingNumber']) && empty($_sourceRecord['UnitNumber']) && ($_sourceRecord['PropertyType'] == 'COND'))
            {
                trace('Searching for Location_Listing record by location w/o BuildingNumber/UnitNumber...');
                $criteria = 'WHERE `Location_id`='.$_sourceRecord['Location_id'];
                $c = $mySql->select('Location_Listing', $criteria)->getRecordsCount();
                trace($c.' records found.');
                if ($c)
                    throw new ImportErrorException(1, ERROR_UNITNUMBER_BUILDINGNUMBER_REQUIRED, 'UnitNumber/BuildingNumber needed for Address.');
            }
            
            
            // Extract Real_Listing record
            $realListing = get_real_listing_by_location($_sourceRecord);
            $_sourceRecord['Property_id'] = $realListing['Property_id'];

            // Restore Location_Listing record
            $locationListing = add_location_listing($_sourceRecord);
            $locationListing['Restored'] = true;
        }
        
        elseif ($c == 1)
        {
            trace('Related Location_Listing found.');
            
            // Extract Location_Listing record
            $locationListing = $mySql->fetchAssoc();
            if ($_sourceRecord['PropertyType'] == 'RES') // RES or COND
            {
                $sourcePropertyType = ($_sourceRecord['PropertySubType'] == 'ATTSF' ? 'COND' : 'RES');
                if ($sourcePropertyType != $locationListing['PropertyType'])
                    trace('WARNING: Source and found PropertyType do not match.');
            }
        }
        
        else 
		{
			trace("Multiple Locatin_Listing records found...");
            //throw new Exception('DATA ERROR: '.$c.' records found.');

			/********************************** New code inserted by Neel on 26 March 2015**********************************/ 
			trace("PropertyType = ".$_sourceRecord['PropertyType']."...");
			 if ($_sourceRecord['PropertyType'] == 'RES')
			{
				 trace("delete all Location_Listing records matching Location_id where both Unit# & Building# are empty...");
				$mySql->delete('Location_Listing', 'WHERE Location_id='.$_sourceRecord['Location_id'].' AND BuildingNumber="" AND UnitNumber=""');
			}
			 if ($_sourceRecord['PropertyType'] == 'COND')
			{
				 if ($_sourceRecord['StreetBuildingNumber'] && $_sourceRecord['UnitNumber'])
				{
					 trace(" delete Location_Listing records matching Location_id, Unit# & Building#...");
					 $mySql->delete('Location_Listing', 'WHERE Location_id='.$_sourceRecord['Location_id'].' AND BuildingNumber='.$_sourceRecord['StreetBuildingNumber'].' AND UnitNumber='.$_sourceRecord['UnitNumber']);
				}
				if ($_sourceRecord['StreetBuildingNumber']=="" && $_sourceRecord['UnitNumber'])
				{
					trace("delete Location_Listing records matching Location_id, Unit# & Empty Building#...");
					$mySql->delete('Location_Listing', 'WHERE Location_id='.$_sourceRecord['Location_id'].' AND BuildingNumber="" AND UnitNumber='.$_sourceRecord['UnitNumber']);
				}
			}
			add_location_listing($_sourceRecord);
			/********************************** ***********************************************************************/ 
        }
        return $locationListing;
    }
    
    
    function get_real_listing_by_location(&$_sourceRecord)
    {
        global $mySql;
        
        trace('Searching for related Real_Listing record by location (Location_id='.$_sourceRecord['Location_id'].', PropertyType='.$_sourceRecord['PropertyType'].', PropertySubType='.$_sourceRecord['PropertySubType'].', BuildingNumber='.$_sourceRecord['StreetBuildingNumber'].', UnitNumber='.$_sourceRecord['UnitNumber'].')...');
        $criteria = 'WHERE `Location_id`='.$_sourceRecord['Location_id'];
        if ($_sourceRecord['PropertyType'] == 'RES') // 'RES' or 'COND'
        {
            if (empty($_sourceRecord['StreetBuildingNumber']))
                $criteria .= ' AND (`BuildingNumber` IS NULL OR `BuildingNumber`=\'\' OR `BuildingNumber`=\'0\')';
            else
                $criteria .= ' AND `BuildingNumber` = \''.mysql_real_escape_string($_sourceRecord['StreetBuildingNumber']).'\'';
            if (empty($_sourceRecord['UnitNumber']))
                $criteria .= ' AND (`UnitNumber` IS NULL OR `UnitNumber`=\'\' OR `UnitNumber`=\'0\')';
            else
                $criteria .= ' AND `UnitNumber` = \''.mysql_real_escape_string($_sourceRecord['UnitNumber']).'\'';
        }
        $criteria .= ' ORDER BY `ListingNumber` DESC LIMIT 1';
        $c = $mySql->select('Real_Listing', $criteria)->getRecordsCount();
        
        
        if ($c == 0)
        {
            trace('Related Real_Listing record not found.');
            
            // Restore Real_Listing record
            $propertyId = add_property($_sourceRecord);
            $realListing = $mySql->select('Real_Listing', 'WHERE `Property_id`='.$propertyId)->fetchAssoc();
            $_sourceRecord['FoundID'] = 1;
            $_sourceRecord['FoundMLSNumber'] = 1;
            
            
            // Replace Location_Listing.Property_id
            trace('Replacing Location_Listing.Property_id (1)...');
            $criteria = 'WHERE `Location_id`='.$_sourceRecord['Location_id'];
            if ($_sourceRecord['PropertyType'] == 'RES') // 'RES' or 'COND'
            {
                if (empty($_sourceRecord['StreetBuildingNumber']))
                    $criteria .= ' AND (`BuildingNumber` IS NULL OR `BuildingNumber`=\'\' OR `BuildingNumber`=\'0\')';
                else
                    $criteria .= ' AND `BuildingNumber` = \''.mysql_real_escape_string($_sourceRecord['StreetBuildingNumber']).'\'';
                if (empty($_sourceRecord['UnitNumber']))
                    $criteria .= ' AND (`UnitNumber` IS NULL OR `UnitNumber`=\'\' OR `UnitNumber`=\'0\')';
                else
                    $criteria .= ' AND `UnitNumber` = \''.mysql_real_escape_string($_sourceRecord['UnitNumber']).'\'';
            }
            $mySql->update('Location_Listing', array('Property_id' => $realListing['Property_id']), $criteria);
        }
        
        elseif ($c == 1)
        {
            trace('Related Real_Listing record found.');
            
            // Extract Real_Listing record
            $realListing = $mySql->fetchAssoc();
            if ($_sourceRecord['PropertyType'] == 'RES') // RES or COND
            {
                $sourcePropertyType = ($_sourceRecord['PropertySubType'] == 'ATTSF' ? 'COND' : 'RES');
                if ($sourcePropertyType != $realListing['PropertyType'])
                    trace('WARNING: Source and found PropertyType do not match.');
            }
        }
        
        else // Multiple Real_Listing records found
            throw new Exception('DATA ERROR: '.$c.' records found.');
        return $realListing;
    }
    
    
    function get_real_listing_by_property_id($_propertyId, &$_sourceRecord)
    {
        global $mySql;
        // Search for related Real_Listing record by Property_id...
        trace('Searching for related Real_Listing record  by property ID - '.$_propertyId);
        $c = $mySql->select('Real_Listing', 'WHERE `Property_id`='.$_propertyId)->getRecordsCount();
        if ($c == 0)
        {
            trace('Related Real_Listing record not found.');
            
            // Restore Real_Listing record
            $propertyId = add_property($_sourceRecord);
            $realListing = $mySql->select('Real_Listing', 'WHERE `Property_id`='.$propertyId)->fetchAssoc();
            $_sourceRecord['FoundID'] = 1;
            $_sourceRecord['FoundMLSNumber'] = 1;
            
            
            // Replace Location_Listing.Property_id
            trace('Replacing Location_Listing.Property_id (2)...');
            $mySql->update('Location_Listing', array('Property_id' => $realListing['Property_id']), 'WHERE `Property_id`='.$propertyId);
        }
        
        elseif ($c == 1)
        {
            trace('Related Real_Listing record found.');
            
            // Extract Real_Listing record
            $realListing = $mySql->fetchAssoc();
        }
        
        else // Multiple Real_Listing records found
            throw new Exception('DATA ERROR: '.$c.' records found.');
        return $realListing;
    }
    
	function update_property($_currentData, $_importData, $_updateFeatures = true)
    {
        global $mySql;
        global $realListingUpdateKeymap;
        
        $updateData = array();
        
        // Update `ListOffice_id` if changed
        if (!empty($_importData['ListOfficeMLSID']))
        {
            $newOfficeId = $mySql->query('
                SELECT `Office_id` FROM `Real_Office`
                    WHERE `ListOfficeId`=\''.mysql_real_escape_string($_importData['ListOfficeMLSID']).'\'
            ')->fetchCellValue('Office_id');
            if ($newOfficeId != $_currentData['ListOffice_id'])
                $updateData['ListOffice_id'] = $newOfficeId;
        }
        
        // Update `ListAgent_id` if changed
        if (!empty($_importData['ListAgentMLSID']))
        {
            $newAgentId = $mySql->query('
                SELECT `Agent_id` FROM `Real_Agent`
                    WHERE `ListAgentId`=\''.mysql_real_escape_string($_importData['ListAgentMLSID']).'\''
            )->fetchCellValue('Agent_id');
            if ($newAgentId != $_currentData['ListAgent_id'])
                $updateData['ListAgent_id'] = $newAgentId;
        }
        
        // Update ListingId if empty
        if (empty($_currentData['ListingId']))
            $updateData['ListingId'] = $_importData['Matrix_Unique_ID'];
        
        // Update others `Real_Listing` fields
        foreach ($realListingUpdateKeymap as $destKey => $srcKey)
        {
            if (!array_key_exists($srcKey, $_importData))
                trace('Real_Listing update keys mismatch: \''.$srcKey.'\' => \''.$destKey.'\'');
            else
                $updateData[$destKey] = $_importData[$srcKey];
        }
        if ($_importData['GarageSpacesCount'] > 0)
            $updateData['CarStorage'] = 'G';
        if (count($updateData))
        {
            trace('Updating Real_Listing\'s data...');
            $mySql->update('Real_Listing', $updateData, 'WHERE `Property_id`='.$_currentData['Property_id']);
        }
        
        
        // Update Real_Text record
       // if ($_updateFeatures)
            add_real_text($_currentData['Property_id'], $_importData);
    }
    
    
    // function add_real_text_public($_propertyId, $_text)
    // {
        // global $mySql;
        // if (!empty($_text))
        // {
            // trace('Adding `Real_Text` PUBLIC record...');
            // $realText = array(
                // 'Property_id' => $_propertyId,
                // 'TextSource' => 'PUBLIC',
                // 'Comments' => $_text,
                // 'TextSum' => crc32($_text),
                // 'Type' => 'F',
                // 'Public' => 1,
            // );
            // $mySql->insert('Import_Text', $realText);
        // }
    // }
    
    
    function add_real_text_remarks($_propertyId, $_text)
    {
        global $mySql;
        if (!empty($_text))
        {
            trace('Adding `Real_Text` REMARKS record...');
            $realText = array(
                'Property_id' => $_propertyId,
                'TextSource' => 'REMARKS',
                'Comments' => $_text,
                'TextSum' => crc32($_text),
                'Type' => 'F',
                'Public' => 1,
            );
            $mySql->insert('Import_Text', $realText);
        }
    }
    
    
    function add_real_text($_propertyId, $_importData)
    {
        global $mySql;
        global $realFeaturesKeys;
        
        
        // Search for Real_Features record
        $realFeaturesFound = $mySql->select(
            'Real_Features',
            'WHERE `Property_id`='.$_propertyId
        )->getRecordsCount();
        
        if ($realFeaturesFound)
        {
            // Extract real features record
            $realFeatures = $mySql->fetchAssoc();
            unset($realFeatures['Property_id']);
            unset($realFeatures['ListingId']);
            unset($realFeatures['Modified']);
            unset($realFeatures['Entered']);
        }
        
        else
        {
            // Build real feature record
            $realFeatures = array(
                'Property_id' => $_propertyId,
                'ListingId' => $_importData['Matrix_Unique_ID'],
                'Entered' => date('Y-m-d H:i:s'),
            );
        }
        
        
        $realTextText = '';
        if (count($realFeaturesKeys))
        {
            foreach ($realFeaturesKeys as $featuresKey)
            {
                if (!$realFeaturesFound
                        or  ($realFeatures[$featuresKey] != $_importData[$featuresKey]
                            and !empty($_importData[$featuresKey])))
                {
                    $realFeatures[$featuresKey] = $_importData[$featuresKey];
                    if (array_key_exists($featuresKey.'_Decoded', $_importData))
                    {
                        $realFeatures[$featuresKey.'_Decoded'] = $_importData[$featuresKey.'_Decoded'];
                        if (!empty($_importData[$featuresKey.'_Decoded']))
                            $realTextText .= $_importData[$featuresKey.'_Decoded'].',';
                    }
                }
            }
            if ($realFeaturesFound)
                $mySql->update('Real_Features', $realFeatures, 'WHERE `Property_id`='.$_propertyId);
            else
                $mySql->insert('Real_Features', $realFeatures);
        }
        $realTextText = trim($realTextText, ',');
        
        $c = $mySql->count(
            'Real_Text',
            'WHERE `Property_id`='.$_propertyId.'
                AND `TextSource`=\'REMARKS\'
                AND `TextSum`=\''.crc32($realTextText).'\''
        );
        if ($c == 0)
        {
            add_real_text_remarks($_propertyId, $realTextText);
            if ($mySql->count('Keyword_Rerun', 'WHERE `Property_id`='.$_propertyId))
                $mySql->query('UPDATE `Keyword_Rerun` SET `Modified`=CURRENT_TIMESTAMP() WHERE `Property_id`='.$_propertyId);
            else
                $mySql->insert('Keyword_Rerun', array('Property_id' => $_propertyId));
        }
    }

?>