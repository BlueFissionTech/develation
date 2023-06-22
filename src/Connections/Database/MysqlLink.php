<?php
namespace BlueFission\Connections\Database;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\DevString;
use BlueFission\Net\HTTP;
use BlueFission\Connections\Connection;
use BlueFission\Behavioral\IConfigurable;

/**
 * Class MysqlLink
 *
 * This class extends the Connection class and implements the IConfigurable interface.
 * It is used for establishing a connection to a MySQL database and performing queries.
 */
class MysqlLink extends Connection implements IConfigurable
{
    // Constants for different types of queries
    const INSERT = 1;
    const UPDATE = 2;
    const UPDATE_SPECIFIED = 3;

    // protected property to store the database connection
    protected static $_database;
    private static $_query;
    private static $_last_row;
    
    // property to store the configuration
    protected $_config = array( 
        'target'=>'localhost',
        'username'=>'',
        'password'=>'',
        'database'=>'',
        'table'=>'',
        'port'=>3306,
        'key'=>'_rowid',
        'ignore_null'=>false,
    );
    
    /**
     * Constructor method.
     *
     * This method sets the configuration, if provided, and sets the connection property to the last stored connection.
     *
     * @param mixed $config The configuration for the connection.
     * @return MysqlLink 
     */
    public function __construct( $config = null )
    {
        parent::__construct( $config );
        if (DevValue::isNull(self::$_database)) {
            self::$_database = [];
        } else {
            $this->_connection = end( self::$_database );
        }
        return $this;
    }
    
    /**
     * Method to open a connection to a MySQL database.
     *
     * This method uses the configuration properties to establish a connection to a MySQL database.
     * If a connection is successfully established, it sets the connection property and the status property.
     *
     * @return void 
     */
    public function open()
    {
        $host = ( $this->config('target') ) ? $this->config('target') : 'localhost';
        $username = $this->config('username');
        $password = $this->config('password');
        $database = $this->config('database');
        $port = $this->config('port');
        
        $connection_id = count(self::$_database);
        
        if ( !class_exists('mysqli') ) return;
        $db = $connection_id > 0 ? end(self::$_database) : new \mysqli($host, $username, $password, $database, $port);
        
        if (!$db->connect_error) {
            self::$_database[$connection_id] = $this->_connection = $db;
        }
        
        $this->status( $db->connect_error ? $db->connect_error : self::STATUS_CONNECTED );
    }
    
	/**
	 * Close the database connection
	 */
	public function close()
	{
		$this->_connection->close();
		
		// Clean up
		parent::close();
	}

	/**
	 * Get stats about the current query
	 *
	 * @return array  An array containing the current query
	 */
	public function stats()
	{
		return ['query'=>$this->_query];
	}
	
	/**
	 * Perform a query on the database
	 *
	 * @param string|array $query  The query to perform
	 * @return bool  Whether the query was successful
	 */
	public function query ( $query = null )
	{
		$db = $this->_connection;
	
		if ( $db )
		{
			
			if (DevValue::isNotNull($query))
			{
				$this->_query = $query;

				if (DevArray::isAssoc($query))
				{
					$this->_data = $query; 
				}
				else if (is_string($query))
				{
					try {
						$this->_result = $db->query($query);
						$this->status( $db->error ? $db->error : self::STATUS_SUCCESS );
						return true;
					} catch ( \Exception | \MySQLiQueryException $e ) {
						$this->_result = false;
						$this->status( self::STATUS_FAILED );
						return false;
					}
				}
			}
			$table = $this->config('table');
			
			$where = '';
			$update = false;
			
			$key = $this->config('key');

			if ($this->field($key) )
			{
				$value = self::sanitize( $this->field($key) );
				$keyField = self::sanitize( $this->config('key') );
				$keyField = '`'.$this->config('key').'`';
				$where = $key ? "$keyField = $value" : '';
				$update = true;
			}
			$data = $this->_data;
			$type = ($update) ? ($this->config('ignore_null') ? self::UPDATE_SPECIFIED : self::UPDATE) : self::INSERT;
			return $this->post($table, $data, $where, $type);	
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return false;
		}	
	}

	/**
	 * Find a record in the database matching the given criteria
	 *
	 * @param string $table  The name of the table to search in
	 * @param array $data  The criteria to match
	 * @return bool  Whether the query was successful
	 */
	private function find($table, $data) 
	{
		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$updates = [];
			$temp_values = [];
			$where = [1];
			$where_str = '';
			$query_str;
			
			foreach ($data as $a) array_push($temp_values, self::sanitize($a));
			
			$count = 0;
			foreach (array_keys($data) as $a) 
			{
				array_push($where, $a ."=". $temp_values[$count]);
				$count++;
			}
	
			$where_str = implode(', ', $where);
			
			$query = "SELECT * FROM `".$table."` WHERE ".$where_str;
			
			$this->_query = $query;

			// $query_str = $query;
			 
			$success = ( $db->query($query) ) ? true : false;
			// $this->_result = $success;
			
			$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return $success;
		}
		
		$this->status($status);
		return $success;
	}
	
	/**
	 * Inserts data into a MySQL database. 
	 * 
	 * @param string $table The name of the database table
	 * @param array $data An associative array of fields and values to be inserted
	 * 
	 * @return boolean Returns `true` if the insert was successful, `false` otherwise
	 */
	private function insert($table, $data) 
	{
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$insert = [];
			$field_string = '';
			$value_string = '';
			$temp_values = [];
			
			// Turn array into a string
			
			// Prepare each value for input
			foreach ($data as $a) {
				array_push($temp_values, self::sanitize($a));
			}

			$count = 0;
			foreach (array_keys($data) as $a) 
			{
				if ($temp_values[$count] !== null && $temp_values[$count] !== 'NULL') {
					// array_push($insert, $temp_values[$count]);
					$insert[$a] = $temp_values[$count];
				}
				
				$count++;
			}
			
			$field_string = implode( '`, `', array_keys($insert));
			$value_string = implode(', ', $insert);
			
			$query = "INSERT INTO `".$table."`(`".$field_string."`) VALUES(".$value_string.")";

			$this->_query = $query;

			try {
				$success = ( $db->query($query) ) ? true : false;
				$status = ($success) ? $db->error : self::STATUS_SUCCESS;
			} catch ( \Exception | \MySQLiQueryException $e ) {
				$success = false;
				$this->status( $e->getMessage() );
			}
			
			$this->_result = $success;
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return $success;
		}
		
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return $success;
	}

	/**
	 * Updates data in a MySQL database. 
	 * 
	 * @param string $table The name of the database table
	 * @param array $data An associative array of fields and values to be updated
	 * @param string $where The condition for which to update the data
	 * @param boolean $ignore_null Takes either a `1` or `0` (`true` or `false`) and determines if the entry 
	 *   will be replaced with a null value or kept the same when `NULL` is passed
	 * 
	 * @return boolean Returns `true` if the update was successful, `false` otherwise
	 */
	private function update($table, $data, $where, $ignore_null = false) 
	{
		$db = $this->_connection;
		$success = false;
		$status = self::STATUS_NOTCONNECTED;

		if ($db)
		{
			$updates = [];
			$temp_values = [];
			$update_string = '';
			$query_str;
			
			foreach ($data as $a) array_push($temp_values, self::sanitize($a));
			
			$count = 0;
			foreach (array_keys($data) as $a) 
			{
				//convert into query string
				if ($ignore_null === true ) 
				{
					// $temp_values[$count] = $this->getExistingValueIfNull($table, $a, $temp_values[$count], $where);
					if ($temp_values[$count] !== null && $temp_values[$count] !== 'NULL') {
						array_push($updates, "`{$a}`" ."=". $temp_values[$count]);
					}
				} else {
					array_push($updates, "`{$a}`" ."=". $temp_values[$count]);
				}
				$count++;
			}
	
			$update_string = implode(', ', $updates);
			
			$query = "UPDATE `".$table."` SET ".$update_string." WHERE ".$where;

			$this->_query = $query;
			// $query_str = $query;
			 
			try {
				$success = ( $db->query($query) ) ? true : false;
				$status = ($success) ? $db->error : self::STATUS_SUCCESS;
			} catch ( \Exception | \MySQLiQueryException $e ) {
				$success = false;
				$this->status( $e->getMessage() );
			}
			
			$this->_result = $success;
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return $success;
		}
		
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		return $success;
	}

	//Posts data into the database using specified method
	/**
	 * Posts data into the database using specified method
	 * 
	 * @param string $table The name of the database table
	 * @param array $data An associative array of fields and values to be affected
	 * @param string $where A MySQL WHERE clause (optional)
	 * @param int $type Determines the type of query used. 1 for INSERT, 2 for UPDATE, 3 for UPDATE ignoring nulls (optional)
	 * 
	 * @return string A string indicating success or error statement
	 */
	private function post($table, $data, $where = null, $type = null) 
	{
		$db = $this->_connection;
		$status = '';
		$success = false;
		$ignore_null = false;
		$last_row = null; 

		if ($where == '' && ($type == self::INSERT || $type == self::UPDATE)) 
		{
			$where = "1";
		} 
		elseif (isset($where) && $where != '' && $type != self::UPDATE_SPECIFIED) 
		{
			$type = self::UPDATE;
		}
		if (isset($table) && $table != '') 
		{ //if a table is specified
			if (count($data) >= 1) 
			{ //validates number of fields and values
				switch ($type) 
				{
				case self::INSERT:
					//attempt a database insert
					if ($this->insert($table, $data)) 
					{
						$status = "Successfully Inserted Entry.";
						$last_row = $db->insert_id;
						$success = true;
					} 
					else 
					{
						$status = "Insert Failed. Reason: " . $db->error;
					}
					break;
				case self::UPDATE_SPECIFIED:
					$ignore_null = true;
				case self::UPDATE:
					//attempt a database update
					if (isset($where) && $where != '') 
					{
						if ($this->update($table, $data, $where, $ignore_null)) 
						{
							$status = "Successfully Updated Entry.";
							$last_row = $db->insert_id;
							$success = true;
						} 
						else 
						{
							$status = "Update Failed. Reason: " . $db->error;
						}
					} 
					else 
					{
						//if where clause is empty
						$status = "No Target Entry Specified.";
					}
				break;
				default:
					//if type is not registered
					$status = "Query Type Not Supported.";
					break;
				}
			} 
			else 
			{
				//if the arrays do not align or match
				$status = "Fields and Values do not match or Insufficient Fields.";
			}
		} 
		else 
		{
			//no table has been assigned
			$status = "No Target Table Specified";
		}
		
		$this->status($status);
		
		$this->_last_row = $last_row ? $last_row : $this->_last_row;

		return $success;
	}
	
	/**
	 * Get or set the database name
	 *
	 * @param string|null $database The database name to set
	 * @return string|null The database name
	 */
	public function database( $database = null )
	{
		if ( DevValue::isNull( $database ) )
			return $this->config('database');

		$this->config('database', $database);
		$db = $this->_connection;
		$db->select_db( $this->config('database') );	
	}

	/**
	 * Get the existing value if NULL is passed as the value
	 *
	 * @param string $table The database table to search in
	 * @param string $field The column that the value is in
	 * @param string $value The original value to be checked or preserved
	 * @param string $where The where clause that determines the row of the entry
	 * @return string The value after checking or preserving
	 */
	private function getExistingValueIfNull($table, $field, $value, $where) 
	{
		$db = $this->_connection;
		
	     if ($value == 'NULL') {
	          $query = "SELECT `$field` FROM `$table` WHERE $where";
	          $result = $db->query($query);
	          $selection = $result->fetch_array();
	          // if (mysql_) // TODO figure out what I intended to do here
	          $this->status( $db->connect_error ? $db->connect_error : self::STATUS_CONNECTED );
	          $value = self::sanitize($selection[0]);
	     }
	          
	     return $value;
	}

	/**
	 * Get the last row inserted
	 *
	 * @return integer The last row id
	 */
	public function last_row() {
		return $this->_last_row;
	}

	/**
	 * Sanitize the given string
	 *
	 * @param string $string The string to sanitize
	 * @param boolean $datetime Indicates if the string is a datetime value
	 * @return string The sanitized string
	 */
	public static function sanitize($string, $datetime = false) 
	{
		$db = end ( self::$_database );
		//Create regular expression patterns
		// $pattern = [ '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d+)\/(\d+)\/(\d{4})/', '/\'(\d)\'/', '/\$/', '/^\'\'$/' ];
		// $replacement = [ '\'', '\'$1\'', '$3-$1-$2', '\'$1\'', '$', 'NULL' ];
		
		// if ($datetime === true) {
		// 	$replacement = [ '\'', '\'$1\'', '$3-$1-$2 12:00:00', '$1', '$', 'NULL' ];
		// }

		$pattern = [ '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d+)\/(\d+)\/(\d{4})/', '/\'(\d)\'/', '/\$/', '/^\'\'$/' ];
		$replacement = [ '\'', '\'$1\'', '$3-$1-$2', '\'$1\'', '$', '' ];
		
		if ($datetime === true) {
			$replacement = [ '\'', '\'$1\'', '$3-$1-$2 12:00:00', '$1', '$', '' ];
		}

		$string = new DevString($string, true);

		$string->constraint(function(&$value) {
			if (DevValue::isNull($value)) {
				$value = 'NULL';
			}
		});

		$string->constraint(function(&$value) {
			if (DevValue::isNull($value) || DevValue::isEmpty($value) || DevString::length($value) <= 0) {
				$value = '';
			}
		});

		$string->constraint(function(&$value) use ($db, $pattern, $replacement) {
			if (DevValue::isNotNull($value)) {
				$value = preg_replace($pattern, $replacement, $db->real_escape_string(stripslashes($value)));
			}
		});

		$string->constraint(function(&$value) {
			if ($value == '\'NOW()\'') {
				$value = 'NOW()';
			}
		});

		$string->constraint(function(&$value) {
			if ($value == '\'NULL\'') {
				$value = 'NULL';
			}
		});
		
		// if ( DevValue::isNotNull($string) ) {
		// 	$string = preg_replace($pattern, $replacement, $db->real_escape_string(stripslashes($string)));
		// }
		
		// if ( DevValue::isNull($string) || DevString::length($string) <= 0) {
		// 	$string = 'NULL';
		// }

		// if ($string == '\'NOW()\'') {
		// 	$string = 'NOW()';
		// }
		
		return $string();
	}

	/**
	 * Determines if the given table exists in the database
	 *
	 * @param string $table Name of the table to check for
	 *
	 * @return bool true if the table exists, false otherwise
	 */
	static function tableExists($table)
	{
	    $db = end( self::$_database );
	    $table = self::sanitize($table);
	    $result = $db->query("SHOW TABLES LIKE {$table}");

	    if ($result && $result->num_rows == 1) {
	        return true;
	    } else {
	        return false;
	    }
	}
}