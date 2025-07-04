<?php
namespace BlueFission\Connections\Database;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Connections\Connection;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class MySQLLink
 *
 * This class extends the Connection class and implements the IConfigurable interface.
 * It is used for establishing a connection to a MySQL database and performing queries.
 */
class MySQLLink extends Connection implements IConfigurable
{
    // Constants for different types of queries
    const INSERT = 1;
    const UPDATE = 2;
    const UPDATE_SPECIFIED = 3;

    // protected property to store the database connection
    protected static $_database;
    protected static $_default = null;
    private $_query;
    private $_last_row;
    
    // property to store the configuration
    protected $_config = [
        'target'=>'localhost',
        'username'=>'',
        'password'=>'',
        'database'=>'',
        'table'=>'',
        'port'=>3306,
        'key'=>'_rowid',
        'ignore_null'=>false,
    ];
    
    /**
     * Constructor method.
     *
     * This method sets the configuration, if provided, and sets the connection property to the last stored connection.
     *
     * @param mixed $config The configuration for the connection.
     * @return MySQLLink 
     */
    public function __construct( $config = null )
    {
        parent::__construct( $config );
        if (Val::isNull(self::$_database)) {
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
    protected function _open(): void
    {
		if ( $this->_connection ) {
			return;
		}

        $host = ( $this->config('target') ) ? $this->config('target') : 'localhost';
        $username = $this->config('username');
        $password = $this->config('password');
        $database = $this->config('database');
        $port = $this->config('port');
        
        $connection_id = Arr::size(self::$_database);
        
        if ( !class_exists('mysqli') ) {
        	throw new \Exception("mysqli not found");
    	}

        $db = $connection_id > 0 ? end(self::$_database) : new \mysqli($host, $username, $password, $database, $port);
        
        if (!$db->connect_error) {
            self::$_database[$connection_id] = $this->_connection = $db;

            $status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;

			$this->perform( $this->_connection 
				? [Event::SUCCESS, Event::CONNECTED, State::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );
        } else {    
	        $status = $db->connect_error ?? self::STATUS_FAILED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );
		}

		$this->status($status);
    }
    
	/**
	 * Close the database connection
	 */
	protected function _close(): void
	{
		if ($this->_connection) {
			$this->_connection->close();
			$this->_connection = null;
		}
		$this->perform(State::DISCONNECTED);
	}

	/**
	 * Set the default connection to the last connection in the static $_database array
	 * or to a specific connection ID if provided. 
	 *
	 * @param int|null $connectionId The connection ID to set as default. 
	 * @return IObj The current instance for method chaining.
	 */
	public function setDefaultConnection( $connectionId = null ): IObj
	{
		if (Val::isNull($connectionId)) {
			self::$_default = end(self::$_database);
		} else {
			if (Arr::hasKey(self::$_database, $connectionId)) {
				self::$_default = self::$_database[$connectionId];
			} else {
				self::$_default = null;
			}
		}

		return $this;
	}

	/**
	 * Get or set the connection ID for the current database connection
	 *
	 * This method allows you to set a specific connection ID or retrieve the current connection ID
	 * or finds the index of the current connection in the static $_database array.
	 * If the connection is not found, it returns the last index of the array.
	 * 
	 * @param int|null $id The connection ID to set. If null, it will return the current connection ID.
	 *
	 * @return ?int The connection ID
	 */
	public function connectionId( $id = null ): ?int
	{

		if (Val::isNotNull($id)) {
			if (!Arr::hasKey(self::$_database, $id)) {
				return null;
			}

			$this->_connection = self::$_database[$id] ?? null;
			return $id;
		}

		$id = Arr::search(self::$_database, $this->_connection);

		if (Val::isNull($id)) {
			$id = Arr::size(self::$_database) - 1;
		}

		if ($id < 0) {
			$id = null;
		}

		return $id;
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
	 * @return IObj
	 */
	public function query( $query = null ): IObj
	{
		$this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

		$db = $this->_connection;
	
		if ( $db )
		{
			
			if (Val::isNotNull($query))
			{
				$this->_query = $query;

				if (Arr::isAssoc($query))
				{
					$this->_data->val($query);
				}
				else if (Str::is($query))
				{
					try {
						$this->_result = $db->query($query);
						$this->status( $db->error ? $db->error : self::STATUS_SUCCESS );
					} catch ( \Exception | \MySQLiQueryException $e ) {
						$this->_result = false;
						$this->status( self::STATUS_FAILED );
					}

					return $this;
				}
			}
			$table = $this->config('table');
			
			$where = '';
			$update = false;
			
			$key = $this->config('key');

			if ( $this->field($key) )
			{
				$value = self::sanitize( $this->field($key) );
				$keyField = self::sanitize( $this->config('key') );
				$keyField = '`'.$this->config('key').'`';
				$where = $key ? "$keyField = $value" : '';
				$update = true;
			}
			$data = $this->_data;
			$type = ($update) ? ($this->config('ignore_null') ? self::UPDATE_SPECIFIED : self::UPDATE) : self::INSERT;
			$this->post($table, $data, $where, $type);
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status ) );
			$this->status( $status );
		}

		return $this;
	}

	private function _read(): void
	{
		$table = $this->config('table');
		$data = $this->_data;
		$this->find($table, $data);
	}

	/**
	 * Find a record in the database matching the given criteria
	 *
	 * @param string $table  The name of the table to search in
	 * @param array $data  The criteria to match
	 * @return void
	 */
	private function find($table, $data): void
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
			foreach ($data->keys() as $a) 
			{
				array_push($where, $a ."=". $temp_values[$count]);
				$count++;
			}
	
			$where_str = implode(', ', $where);
			
			$query = "SELECT * FROM `".$table."` WHERE ".$where_str;
			
			$this->_query = $query;

			$this->perform([State::SENDING, State::RECEIVING, State::PROCESSING, State::BUSY]);
			$result = $db->query($query);
			$success = ( $result ) ? true : false;
			$status = ( $success ) ? self::STATUS_SUCCESS : ($db->error ?? self::STATUS_FAILED);
			$this->assign( $result->fetch_object() );
			$this->halt([State::BUSY, State::SENDING, State::RECEIVING, State::PROCESSING]);

			$this->perform([Action::RECEIVE]);
			$this->perform(Event::RECEIVED, new Meta(data: $this->_result));
			
			$status = ( $success ) ? self::STATUS_SUCCESS : ($db->error ?? self::STATUS_FAILED);

			$this->perform( 
				$this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE], 
				new Meta(when: Action::PROCESS, info: $status ) 
			);
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
			$this->status( $status );
		}
		
		$this->status($status);
		
		return;
	}
	
	/**
	 * Inserts data into a MySQL database. 
	 * 
	 * @param string $table The name of the database table
	 * @param array $data An associative array of fields and values to be inserted
	 * 
	 * @return void
	 */
	private function insert($table, $data): void
	{
		$this->perform(State::CREATING, new Meta(when: Action::PROCESS));

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
			foreach ($data as $key => $a) {
				// continue if the key is a primary key
				if ($key == $this->config('key')) {
					continue;
				}
				array_push($temp_values, self::sanitize($a));
			}

			$count = 0;
			foreach ($data->keys() as $a) 
			{
				// continue if the key is a primary key
				if ($a == $this->config('key')) {
					continue;
				}
				
				if ($temp_values[$count] !== null && $temp_values[$count] !== 'NULL') {
					$insert[$a] = $temp_values[$count];
				}
				
				$count++;
			}
			
			$field_string = implode( '`, `', Arr::keys($insert));
			$value_string = implode(', ', $insert);
			
			$query = "INSERT INTO `".$table."`(`".$field_string."`) VALUES(".$value_string.")";

			$this->_query = $query;

			$this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $insert));
			$this->perform([State::PROCESSING, State::BUSY]);
			try {
				$result = $db->query($query);
				$success = ( $result ) ? true : false;
				$status = ( $success ) ? self::STATUS_SUCCESS : ($db->error ?? self::STATUS_FAILED);
				$this->_last_row = $db->insert_id ?? $this->_last_row;
				$this->field($this->config('key'), $this->_last_row);
			} catch ( \Exception | \MySQLiQueryException $e ) {
				error_log($e->getMessage());
				$status = self::STATUS_FAILED;
			    $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $e->getMessage()));
				$success = false;
				$this->status( $e->getMessage() );
			}
			$this->_result = $success;
			$this->halt([State::BUSY, State::SENDING, State::PROCESSING]);

			$this->perform(Event::SENT, new Meta(data: $data->val()));
			$this->perform([Action::RECEIVE]);
			$this->perform(Event::RECEIVED, new Meta(data: $this->_result));
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status ) );
			$this->halt(State::CREATING);
			return;
		}
		
		$this->halt(State::CREATING);
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return;
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
	 * @return void
	 */
	private function update($table, $data, $where, $ignore_null = false): void
	{
		$this->perform(State::UPDATING, new Meta(when: Action::PROCESS));
	
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
			foreach ($data->keys()as $a) 
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
			
			$this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $updates));
			$this->perform([State::PROCESSING, State::BUSY]);
			 
			try {
				$result = $db->query($query);
				$success = ( $result ) ? true : false;
				$status = ( $success ) ? self::STATUS_SUCCESS : ($db->error ?? self::STATUS_FAILED);
			} catch ( \Exception | \MySQLiQueryException $e ) {
				error_log($e->getMessage());
				$status = self::STATUS_FAILED;
			    $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $e->getMessage()));
				$success = false;
				$this->status( $e->getMessage() );
			}
			
			$this->_result = $success;

			$this->halt([State::BUSY, State::SENDING, State::PROCESSING]);

			$this->perform(Event::SENT, new Meta(data: $data->val()));
			$this->perform([Action::RECEIVE]);
			$this->perform(Event::RECEIVED, new Meta(data: $this->_result));
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status ) );
			$this->halt(State::CREATING);
			return;
		}
		
		$this->halt(State::CREATING);
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return;
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
	 * @return void
	 */
	private function post($table, $data, $where = null, $type = null): void
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
			if ($data->size() >= 1) 
			{ //validates number of fields and values
				switch ($type) 
				{
				case self::INSERT:
					//attempt a database insert
					$this->insert($table, $data);
					$status = $this->status();
					break;
				case self::UPDATE_SPECIFIED:
					$ignore_null = true;
				case self::UPDATE:
					//attempt a database update
					if (isset($where) && $where != '') 
					{
						$this->update($table, $data, $where, $ignore_null);
						$status = $this->status();
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
		
		$this->perform( 
			$this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE], 
			new Meta(when: Action::PROCESS, info: $status ) 
		);

		return;
	}
	
	/**
	 * Get or set the database name
	 *
	 * @param string|null $database The database name to set
	 * @return string|null The database name
	 */
	public function database( $database = null )
	{
		if ( Val::isNull( $database ) )
			return $this->config('database');

		$this->config('database', $database);
		
		if ($this->_connection) {
			$this->_connection->select_db( $this->config('database') );
		}

		return $this;
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
	public function lastRow() {
		return $this->_last_row;
	}

	/**
	 * Get the default database connection
	 * 
	 * @return ?\mysqli|null The default database connection
	 */
	protected static function getConnection()
	{
		if (Val::isNull(self::$_database)) {
			return null;
		}

		return self::$_default ?? end(self::$_database);
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
		if (Val::isNull($string)) {
			return 'NULL';
		}
		
		$db = end( self::$_database );
		$pattern = [ '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d+)\/(\d+)\/(\d{4})/', '/\'(\d)\'/', '/\$/', '/^\'\'$/' ];
		$replacement = [ '\'', '\'$1\'', '$3-$1-$2', '\'$1\'', '$', '' ];
		
		if ($datetime === true) {
			$replacement = [ '\'', '\'$1\'', '$3-$1-$2 12:00:00', '$1', '$', '' ];
		}

		$string = new Str($string, true);

		$string->constraint(function(&$value) {
			if (Val::isNull($value)) {
				$value = 'NULL';
			}
		});

		$string->constraint(function(&$value) use ($db, $pattern, $replacement) {
			if (Val::isNotNull($value)) {
				if ($db) {
					$value = $db->real_escape_string(stripslashes($value));
				}
				$value = preg_replace($pattern, $replacement, $value);
			}
		});

		$string->constraint(function(&$value) {
			if (Val::isEmpty($value) || Str::len($value) <= 0) {
				$value = '\'\'';
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
	    if (!$db) {
	    	return false;
	    }
	    
	    $table = self::sanitize($table);
	    $result = $db->query("SHOW TABLES LIKE {$table}");

	    if ($result && $result->num_rows == 1) {
	        return true;
	    } else {
	        return false;
	    }
	}
}