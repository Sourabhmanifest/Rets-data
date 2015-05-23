<?php
    //exit("in");

    // No time limit for this script execution
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    
    // require_once('mysqlconfig-production.php');
	require_once('mysqlconfig-dev.php');
	//require_once('mysqlhandler.php');
    require_once('real_qwords.php');
	require_once('mysqlhandler-2.0.php');
	require_once('process_status_log_insert.php');
    
    // Settings
    //define('END_OF_LINE', "\r\n");
    define('TODAY', date('Y-m-d'));
    define('_PROCESS_NAME', 'Text Analysis');
	define('_PROCESS_ID', '2.12');
    define('DEBUG', false);
    define('DEBUG_RECORDS_LIMIT', 0); // Debug purpose, 0 means no limit
    define('LOG_TO_CONSOLE', false);
    define('LOG_TO_FILE', true);
    define('_LOG_FILEPATH', './logs/keyword_text_analysis_'.TODAY.'.log');
    
    // Process status step
    define('PSS_MAIN', 0);
    
    // Process status
    define('PS_STARTED', 0);
    define('PS_COMPLETED_SUCCESS', 1);
    define('PS_COMPLETED_ERRORS', -1);

	require_once('trace.php');
    
   
    // Create log file
    if (LOG_TO_FILE) $flog = fopen(_LOG_FILEPATH, 'at') or die ('Failed to open log file (\''._LOG_FILEPATH.'\').');
    trace(_PROCESS_NAME.' started...');
    if (DEBUG) trace('WARNING: Debug mode is ON.');
    
    
    $completeStatus = PS_COMPLETED_SUCCESS;
    try
    {
        // Establish database connection
        $mySql = new MySqlHandler(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        $mySql->connect();
        
		// extract last executed date 
        $lastExecuted = $mySql->select('Process_Execution', 'WHERE `Process`=\''._PROCESS_NAME.'\'')->fetchCellValue('Executed');

        // Write process start record
        log_process_status(_PROCESS_NAME, PSS_MAIN, 'Process started', PS_STARTED,_PROCESS_ID);
        
		// Build keyword phrase lookup map
        trace('Building keyword lookup map...');
        $mySql->query('SELECT * FROM `Keyword_Phrase` WHERE 1 ORDER BY `KeyWord` DESC');
        $mySql->fetchAllRecordsAsMap($keywordPhraseById, MYSQL_ASSOC, 'KeyWord_id');

        $keywordLookupMap = array();
        foreach ($keywordPhraseById as $keywordPhraseId => $keywordPhrase)
        {
            if (!empty($keywordPhrase['Token']))
            {
                // Remove redundant whitespaces
                $keywordPhrase['KeyWord'] = remove_redundant_spaces($keywordPhrase['KeyWord']);
                
                // To lower case
                $keywordPhrase['KeyWord'] = mb_strtolower($keywordPhrase['KeyWord'], 'utf8');
                
                // Split keywords on tokens
                $keywordTokens = explode(' ', trim($keywordPhrase['KeyWord']));
                
                // Build map for the current keyword
                $keywordLookupMapRef = &$keywordLookupMap;

                foreach ($keywordTokens as $keywordToken)
				{
                    if (!array_key_exists($keywordToken, $keywordLookupMapRef))
                        $keywordLookupMapRef[$keywordToken] = array();
					$keywordLookupMapRef = &$keywordLookupMapRef[$keywordToken];
                }
                $keywordLookupMapRef[''][] = $keywordPhraseId;
            }
        }
        
        // Extract required properties
        //! Sold only for now
        trace('Extracting properties data...');
        //$query = 'SELECT `Real_Listing`.`Property_id`,`Real_Features`.`Entered` FROM `Real_Listing` LEFT JOIN `Real_Features` ON (`Real_Listing`.`Property_id`=`Real_Features`.`Property_id`) WHERE `Real_Listing`.`Status`=\'S\' AND `Real_Listing`.`Entered` > "'.$lastExecuted.'"';
		//$query = 'SELECT `Real_Listing`.`Property_id`,`Real_Features`.`Modified` FROM `Real_Listing` LEFT JOIN `Real_Features` ON (`Real_Listing`.`Property_id`=`Real_Features`.`Property_id`) WHERE `Real_Listing`.`Status`NOT IN ("W","X") AND `Real_Listing`.`Modified` > "'.$lastExecuted.'"';
		$query = 'SELECT  r.Property_id, f.Modified FROM Real_Listing r, Real_Features f WHERE r.Property_id = f.Property_id AND f.Modified > "'.$lastExecuted.'"';
 

        if(DEBUG and DEBUG_RECORDS_LIMIT)
            $query .= ' LIMIT '.DEBUG_RECORDS_LIMIT;
        $mySql->query($query);
        $c = $mySql->getRecordsCount();
        trace($c.' properties to be processed.');
        // Processing
        if ($c)
        {
            $mySql->fetchAllRecords($properties, MYSQL_ASSOC);
		
            foreach ($properties as $i => $property)
            {
                trace('Processing property '.($i+1).' of '.$c.' (Property_id='.$property['Property_id'].')...');

                 // Extract comments
                $comments = array();

				/*$mySql->query('SELECT `Comments` FROM `Real_Text` WHERE `Property_id`='.$property['Property_id'].' AND (`TextSource`=\'AGENT\' OR `TextSource`=\'PUBLIC\')');
				 $commentsCount = $mySql->getRecordsCount();
                if ($commentsCount)
                    $mySql->fetchAllRecords($comments, MYSQL_ASSOC);*/
				
				$mySql->query('SELECT `PublicRemarks`as `Comments` FROM `Real_Features` WHERE `Property_id`='.$property['Property_id']);
                $commentsCount = $mySql->getRecordsCount();

                if ($commentsCount)
                    $mySql->fetchAllRecords($comments, MYSQL_ASSOC);
					
               // Extract photos captions
                $photos = array();
                $mySql->query('SELECT `Photo_id`, `Caption` FROM `Location_Photo` WHERE `ListingId`='.$property['Property_id']);
                $photosCount = $mySql->getRecordsCount();

                if ($photosCount)
                    $mySql->fetchAllRecords($photos, MYSQL_ASSOC);
                                
                if ($commentsCount == 0  and  $photosCount == 0)
                    trace('Empty.');

                else
                {
                    // Compose analysis strings
                    $strings = array();
                    
                    // Add comments to analysis strings
                    if ($commentsCount)
                    {
                        foreach ($comments as $comment)
                          $strings = array_merge($strings, prepare_strings($comment['Comments']));
                    }
                    
                    // Add photos captions to snalysis strings
                    if ($photosCount)
                    {
                        foreach ($photos as $photo)
                            $strings = array_merge($strings, prepare_strings($photo['Caption']));
					}
                    
                     foreach ($strings as $string)
					{
                        $string = trim($string);
                        trace('Analysing string: \''.$string.'\'...');
                        $stringTokens = explode(' ', $string);

						for ($initialStringTokenPosition = 0; $initialStringTokenPosition < count($stringTokens); ++$initialStringTokenPosition)
                        {
                            $match = false;
                            
                            //$matchPattern = '';
                            $matchPatternStartPosition = $initialStringTokenPosition;
                            $keywordLookupMapRef = &$keywordLookupMap;


                            for ($currentStringTokenPosition = $initialStringTokenPosition; $currentStringTokenPosition < count($stringTokens); ++$currentStringTokenPosition)
                            {
                                $stringToken = $stringTokens[$currentStringTokenPosition];
                                if (!array_key_exists($stringToken, $keywordLookupMapRef))
                                    break;
                                //$matchPattern .= $stringToken.'';

                                $keywordLookupMapRef = &$keywordLookupMapRef[$stringToken];
                            }
                           $matchPatternEndPosition = $currentStringTokenPosition; 
          
                            if (array_key_exists('', $keywordLookupMapRef))
                            {
                                foreach ($keywordLookupMapRef[''] as $keywordId)
                                {
                                    $keywordPhrase = $keywordPhraseById[$keywordId]; 
                                    trace('Analysing for keyword match: \''.$keywordPhrase['KeyWord'].'\' (KeyWord_id='.$keywordId.')...');
                                    if ($keywordPhrase['Relative']==0)
                                    {
                                        trace('Match found (1).');
                                        $match = true;
                                        
                                        // Advance initial string token position
                                        $initialStringTokenPosition = $currentStringTokenPosition - 1;
                                    }
                                    else
                                    {
                                        if ($keywordPhrase['Relative']<0)
                                        {
                                            $adjectiveStartPosition = max(0, $initialStringTokenPosition + $keywordPhrase['Relative']); 
                                            $adjectiveEndPosition = min($initialStringTokenPosition, count($stringTokens));
                                        }
                                        else
                                        {
                                            $adjectiveStartPosition = $currentStringTokenPosition;
                                            $adjectiveEndPosition = min($currentStringTokenPosition + $keywordPhrase['Relative'], count($stringTokens));
                                        }
                                        
                                        $adjectives = explode(',', $keywordPhrase['Adjective']);
                                        foreach ($adjectives as $adjective)
                                        {
											$adjective = trim($adjective);
                                            trace('Analysing for adjective match: \''.$adjective.'\'...');
                                            if (strpos($adjective, ' ') === false)
                                            {
                                                for ($adjectivePosition = $adjectiveStartPosition; $adjectivePosition < $adjectiveEndPosition; ++$adjectivePosition)
                                                {
                                                    if ($stringTokens[$adjectivePosition] == $adjective)
                                                    {
                                                        trace('Match found (2).');
                                                        $match = true;
                                                        
                                                        if ($keywordPhrase['Relative'] < 0)
                                                            $matchPatternStartPosition = $adjectivePosition;
                                                        else
                                                        {
                                                            // Advance initial string token position
                                                            $initialStringTokenPosition = $adjectivePosition;
                                                            
                                                            // Update match pattern end position
                                                            $matchPatternEndPosition = $adjectivePosition + 1;
                                                        }
                                                        
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            else // Multiple tokens adjective
                                            {
                                                $adjectiveTokens = explode(' ', $adjective);
                                                $multipleAdjectiveEndPosition = $adjectiveEndPosition - count($adjectiveTokens) + 1;
                                                for ($adjectivePosition = $adjectiveStartPosition; $adjectivePosition < $multipleAdjectiveEndPosition; ++$adjectivePosition)
                                                {
                                                    $match = true;
                                                    for ($adjectiveTokenPosition = 0; $adjectiveTokenPosition < count($adjectiveTokens); ++$adjectiveTokenPosition)
                                                    {
                                                        if ($stringTokens[$adjectivePosition+$adjectiveTokenPosition] != $adjectiveTokens[$adjectiveTokenPosition])
                                                        {
                                                            $match = false;
                                                            break;
                                                        }
                                                    }
                                                    if ($match)
                                                    {
                                                        trace('Match found (3).');
                                                        if ($keywordPhrase['Relative'] < 0)
                                                        {
                                                            // Update match pattern start position
                                                            $matchPatternStartPosition = $adjectivePosition;
                                                        }
                                                        else // ($keywordPhrase['Relative'] > 0)
                                                        {
                                                            // Advance initial string token position
                                                            $initialStringTokenPosition = $adjectivePosition + $adjectiveTokenPosition - 1;
                                                            
                                                            // Update match pattern
                                                            $matchPatternEndPosition = $initialStringTokenPosition + 1;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($match) break;
                                        }
                                    }
                                    if ($match) break;
                                }
                            }
                            
                            if($match)
                            {
                                $matchPattern = '';
                                for ($pos = $matchPatternStartPosition; $pos < $matchPatternEndPosition; ++$pos)
                                    $matchPattern .= $stringTokens[$pos].' ';
                                $matchPattern = trim($matchPattern);
                                trace('Match pattern: \''.$matchPattern.'\'.');
                                update_or_insert_real_qwords_record($property['Property_id'], '1', $keywordPhrase['QDNA'], $keywordPhrase['Token'], $matchPattern,$keywordPhrase['JumpKey']);
                            }
                        }
                    }
                }
            }
        } 
        // Write process complete record
        log_process_status(_PROCESS_NAME, PSS_MAIN, 'Process completed', $completeStatus,_PROCESS_ID);
    }
    
    catch (Exception $e)
    {
        trace('EXCEPTION: '.$e->getMessage());
        $completeStatus = PS_COMPLETED_ERRORS;
        if (DEBUG) print $e->getTraceAsString();
    }
    
  
    trace(_PROCESS_NAME.' completed.');
    if (LOG_TO_FILE and $flog) fclose($flog);
    $mySql->close();
    
    function remove_redundant_spaces($_string)
    {
        do { $_string = str_replace('  ', ' ', $_string, $c); } while ($c);
        return $_string;
    }
    
    
    function prepare_strings($_text)
    {
        $text = mb_strtolower(remove_redundant_spaces($_text), 'utf8');
        $text = preg_replace('/[^0-9A-Za-z \-]/', '|', $text);
        $text = trim($text, '|');
        do { $text = str_replace('||', '|', $text, $c); } while ($c);
        return explode('|', $text);
    }

?>