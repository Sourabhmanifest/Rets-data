<?php

    /**
     * MySql connect exception
     */
    class MySqlConnectException extends Exception {}
    
    
    /**
     * MySql query exception
     */
    class MySqlQueryException extends Exception {}
    

    /**
     * MySQL handler (2.0)
     *
     * @author Alexander Babayev
     */
    class MySqlHandler
    {

        /**
         * @var string Host name
         */
        protected $hostName;


        /**
         * @var string User name
         */
        protected $userName;


        /**
         * @var string Password
         */
        protected $password;


        /**
         * @var string Database name
         */
        protected $dbName;


        /**
         * @var resource Database server link
         */
        private $link;

        
        /**
         * @var resource Query result resource
         */
        private $result = false;


        /**
         * Constructor
         * @param string $_hostName  Host name
         * @param string $_userName  User name
         * @param string $_password  Password
         * @param string $_dbName    Database name
         */
        public function __construct($_hostName, $_userName, $_password, $_dbName)
        {
            $this->hostName = $_hostName;
            $this->userName = $_userName;
            $this->password = $_password;
            $this->dbName   = $_dbName;
            $this->link     = null;
        }
        
        
        /**
         * Destructor
         */
        public function __desctruct()
        {
            // Free last result and close the connection
            $this->freeResult();
            $this->close();
        }
        
        
        /**
         * Tells if MySQL connection has been established
         */
        public function connected()
        {
            return !is_null($this->link);
        }


        /**
         * Performs connection to the database server
         */
        public function connect()
        {
            // Connect
            $this->link = @mysql_connect($this->hostName, $this->userName, $this->password);
            if ($this->link === false)
                throw new MySqlConnectException(mysql_error());

            // Select database
            if (mysql_select_db($this->dbName) === false)
                throw new MySqlConnectException(mysql_error());
            
            // Set encoding
            $this->query('SET NAMES \'utf8\'');
        }


        /**
         * Closes the database server connection
         */
        public function close()
        {
            if($this->link)
            {
                mysql_close($this->link);
                $this->link = null;
            }
        }

        
        /**
         * Accesses the ID generated from the previous INSERT operation
         * @return integer|boolean ID generated from the previous INSERT operation
         */
        public function getInsertId()
        {
            mysql_insert_id($this->link);
        }

        
        /**
         * Performs SQL query
         * @param string $_query  SQL statemement
         */
        public function query($_query)
        {
            // Free previous result
            $this->freeResult();

            // Perform query
            $this->result = mysql_query($_query, $this->link);
            if ($this->result === false)
                throw new MySqlQueryException(mysql_error());
            
            return $this;
        }
        
        
        /**
         * Frees the result
         */
        public function freeResult()
        {
            if (is_resource($this->result))
            {
                mysql_free_result($this->result);
                $this->result = null;
            }
        }


        /**
         * Extracts record as associative map
         * @return array Record
         */
        public function fetchAssoc()
        {
            return mysql_fetch_assoc($this->result);
        }
        
        
        /**
         * Extracts record
         * @param integer $_resultType Mapping type
         * @return array Record
         */
        public function fetchArray($_resultType = MYSQL_BOTH)
        {
            return mysql_fetch_array($this->result, $_resultType);
        }

        
        /**
         * Extracts all records
         * @param array $_records Records set to be extracted
         * @param integer $_resultType Mapping type
         */
        public function fetchAllRecords(&$_records, $_resultType = MYSQL_BOTH)
        {
            $_records = array();
            while ($record = mysql_fetch_array($this->result, $_resultType))
                $_records[] = $record;
            $this->freeResult();
        }

        
        /**
         * Extracts all records
         * @param integer $_resultType Mapping type
         * @return array Fetched records
         */
        public function fetchAll($_resultType = MYSQL_ASSOC)
        {
            $records = array();
            while ($record = mysql_fetch_array($this->result, $_resultType))
                $records[] = $record;
            $this->freeResult();
            return $records;
        }

        
        /**
         * Extracts all records as name by ID
         * @param array Records set to be extracted
         * @param integer $_resultType Mapping type
         * @param string $_key Mapping key
         */
        public function fetchAllRecordsAsMap(&$_records, $_resultType = MYSQL_BOTH, $_key)
        {
            $_records = array();
            while ($record = mysql_fetch_array($this->result, $_resultType))
                $_records[$record[$_key]] = $record;
            $this->freeResult();
        }

        
        /**
         * Extracts all records as name by ID
         * @param array Records set to be extracted
         * @param integer $_resultType Mapping type
         */
        public function fetchAllRecordsAsNameById(&$_records, $_resultType = MYSQL_BOTH)
        {
            $_records = array();
            while ($record = mysql_fetch_array($this->result, $_resultType))
                $_records[$record['id']] = $record['name'];
            $this->freeResult();
        }
        
        
        /**
         * Accesses the number of records in the result
         * @return integer|boolean The number of records in the result or false if no result
         */
        public function getRecordsCount()
        {
            return mysql_num_rows($this->result);
        }
        
        
        /**
         * Accesses the cell value
         * @param string Cell name
         * @return mixed Cell value on success, null otherwise
         */
        public function fetchCellValue($_cellName)
        {
            $record = mysql_fetch_assoc($this->result);
            return $record !== false ? $record[$_cellName] : null;
        }

        
        /**
         * Accesses the number of affected rows
         * @return int Affected rows count
         */
        public function getAffectedRows()
        {
            return mysql_affected_rows();
        }
        
        
        /**
         * Executes insert query
         * @param string $_tableName Table name
         * @param array $_data Data
         */
        public function insert($_tableName, $_data)
        {
            $query = 'INSERT INTO `'.$_tableName.'` SET ';
            foreach ($_data as $key => $value)
            {
                if (is_null($value))
                    $query .= '`'.$key.'` = NULL, ';
                else
                    $query .= '`'.$key.'` = \''.mysql_real_escape_string($value).'\', ';
            }
            $query = substr($query, 0, strlen($query)-2);
            //echo $query;exit;
			$this->query($query);
            return $this;
        }
        
        /**
         * WARNING: This function is deprecated, use insert instead
         * Inserts record
         * @param string $_tableName Table name
         * @param array $_data Data
         */
        public function insertRecord($_tableName, $_data)
        {
            return $this->insert($_tableName, $_data);
        }

        
        /**
         * WARNING: This function is deprecated, use update instead
         * Updates record
         * @param string $_tableName Table name
         * @param array $_data Data
         * @param string $_criteria Update criteria (condition)
         */
        public function updateRecord($_tableName, $_data, $_criteria = 'WHERE 1')
        {
            $this->update($_tableName, $_data, $_criteria);
        }
        
        
        /**
         * Executes UPDATE query
         * @param string $_tableName Table name
         * @param array $_data Data
         * @param string $_criteria Update criteria (condition)
         */
        public function update($_tableName, $_data, $_criteria = 'WHERE 1')
        {
            $query = 'UPDATE `'.$_tableName.'` SET ';
            foreach ($_data as $key => $value)
            {
                if (is_null($value))
                    $query .= '`'.$key.'`=NULL,';
                else
                    $query .= '`'.$key.'`=\''.mysql_real_escape_string($value).'\',';
            }
            $query = trim($query, ',').' '.$_criteria;
			//echo $query; 
			return $this->query($query);
        }

        
        /**
         * Deletes records with specified criteria
         * @param string $_tableName Table name
         * @param string $_criteria Criteria (condition)
         */
        public function delete($_tableName, $_criteria = 'WHERE 1')
        {
            $query = 'DELETE FROM `'.$_tableName.'` '.$_criteria;
            $this->query($query);
        }
        
        
        /**
         * Executes START TRANSACTION query
         */
        public function startTransaction()
        {
            $this->query('START TRANSACTION');
        }
        
        
        /**
         * Executes ROLLBACK query
         */
        public function rollback()
        {
            $this->query('ROLLBACK');
        }
        
        
        /**
         * Executes COMMIT query
         */
        public function commit()
        {
            $this->query('COMMIT');
        }
        
        
        /**
         * Executes COUNT(*) query
         * @param string $_tableName Table name
         * @param string $_criteria Criteria (condition)
         * @return integer Count value
         */
        public function count($_tableName, $_criteria = 'WHERE 1')
        {
            $this->query('SELECT COUNT(*) AS `count` FROM `'.$_tableName.'` '.$_criteria);
            return $this->fetchCellValue('count');
        }
        
        
        /**
         * Executes SELECT query
         * @param string $_tableName Table name
         * @param string $_criteria Criteria (condition)
         * @return array Extracted records
         */
        public function select($_tableName, $_criteria)
        {
			$query='SELECT * FROM `'.$_tableName.'` '.$_criteria; 
            return $this->query($query);
        }
		public function selectcell($_tableName, $_criteria,$_cellname)
        {
			$query='SELECT '.$_cellname .' FROM `'.$_tableName.'` '.$_criteria;
            return $this->query($query)->fetchCellValue($_cellname);
        }

	public function runQuery($_query)
        {
		mysql_query($_query, $this->link);
        }

    }

?>