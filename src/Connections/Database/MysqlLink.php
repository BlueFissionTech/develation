<?php
namespace BlueFission\Connections\Database;

use BlueFission\DevValue;
use BlueFission\Connections\Connection;
use BlueFission\DevArray;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

class MysqlLink extends Connection implements IConfigurable
{
	const INSERT = 1;
	const UPDATE = 2;
	const UPDATE_SPECIFIED = 3;

	protected static $_database;
	private static $_query;
	private static $_last_row;
	
	protected $_config = array( 'target'=>'localhost',
		'username'=>'',
		'password'=>'',
		'database'=>'',
		'table'=>'',
		'key'=>'_rowid',
		'ignore_null'=>false,
	);
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
		if (DevValue::isNull(self::$_database))
			self::$_database = array();
		else	
			$this->_connection = end ( self::$_database );

		return $this;
	}
	
	public function open()
	{
		$host = ( $this->config('target') ) ? $this->config('target') : 'localhost';
		$username = $this->config('username');
		$password = $this->config('password');
		$database = $this->config('database');
		
		$connection_id = count(self::$_database);
		
		if ( !class_exists('mysqli') ) return;
		$db = new \mysqli($host, $username, $password, $database);
		
		if (!$db->connect_error)
			self::$_database[$connection_id] = $this->_connection = $db;
		
		$this->status( $db->connect_error ? $db->connect_error : self::STATUS_CONNECTED );
	}
	
	public function close()
	{
		$this->_connection->close();
		
		//clean up
		parent::close();
	}

	public function stats()
	{
		return array('query'=>$this->_query);
	}
	
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
					$this->_result = $db->query($query);
					$this->status( $db->error ? $db->error : self::STATUS_SUCCESS );
					return true;
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

	private function find($table, $data) 
	{
		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$updates = array();
			$temp_values = array();
			$where = array(1);
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
	
	//inserts data into a MySQL database. 
	//$table takes a string that represents the name of the database table
	//(old) $fields is an array of all fields to be affected
	//(old) $values is an array of all values to be inserted
	//$data is an associative array of fields and values to be affected
	//returns a true if insert was successful, false if not
	private function insert($table, $data) 
	{
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$field_string = '';
			$value_string = '';
			$temp_values = array();
			
			//turn array to string
			$field_string = implode( ', ', array_keys($data));
			//prepare each value for input
			foreach ($data as $a) array_push($temp_values, self::sanitize($a));
			
			$value_string = implode(', ', $temp_values);
			
			$query = "INSERT INTO `".$table."`(".$field_string.") VALUES(".$value_string.")";

			$this->_query = $query;

			$success = ( $db->query($query) ) ? true : false;

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

	//updates data in a MySQL database. 
	//$table takes a string that represents the name of the database table
	//(old) $fields is an array of all fields to be affected 
	//(old) $values is an array of all values to be changed
	//$data is an associative array of fields and values to be affected
	//returns a true if update was successful, false if not
	//$ignore_null takes either a 1 or 0 (true or false) and determines if the entry 
	//   will be replaced with a null value or kept the same when NULL is passed
	private function update($table, $data, $where, $ignore_null = false) 
	{
		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$updates = array();
			$temp_values = array();
			$update_string = '';
			$query_str;
			
			foreach ($data as $a) array_push($temp_values, self::sanitize($a));
			
			$count = 0;
			foreach (array_keys($data) as $a) 
			{
				//convert into query string
				if ($ignore_null === true) 
				{
					$temp_values[$count] = $this->getExistingValueIfNull($table, $a, $temp_values[$count], $where);
				}
				array_push($updates, $a ."=". $temp_values[$count]);
				$count++;
			}
	
			$update_string = implode(', ', $updates);
			
			$query = "UPDATE `".$table."` SET ".$update_string." WHERE ".$where;
			
			$this->_query = $query;
			// $query_str = $query;
			 
			$success = ( $db->query($query) ) ? true : false;
			$this->_result = $success;
			
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

	//Posts data into DB by whatever means specified
	//$table takes a string that represents the name of the database table
	//(old) $fields is an array of all fields to be affected
	//(old) $values is an array of all values to be changed
	//$data is an associative array of fields and values to be affected
	//$where takes a MySQL where clause. Uses an update query if given.
	//$type determines what type of query will be used. 1 gives an insert, 2 gives an update, 3 give and update ignoring nulls
	//Returns string with error or success statement.
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
	
	public function database( $database = null )
	{
		if ( DevValue::isNull( $database ) )
			return $this->config('database');
		
		$this->config('database', $database);
		$db = $this->_connection;
		$db->select_db( $this->config('database') );	
	}
		
	//Determines if the entry will be replaced with a null value or kept the same when NULL is passed
	//$table takes the databased table to search in
	//$field is the column that the value is in
	//$value is the original value to be checked or preserved
	//$where is the where clause that determines the row of the entry
	private function getExistingValueIfNull($table, $field, $value, $where) 
	{
		$db = $this->_connection;
		
	     if ($value == 'NULL') {
	          $query = "SELECT `$field` FROM `$table` WHERE $where";
	          $result = $db->query($query);
	          $selection = $result->fetch_array();
	          if (mysql_)
	          $this->status( $db->connect_error ? $db->connect_error : self::STATUS_CONNECTED );
	          $value = self::sanitize($selection[0]);
	     }
	          
	     return $value;
	}

	public function last_row() {
		return $this->_last_row;
	}
	
	public static function sanitize($string, $datetime = false) 
	{
		$db = end ( self::$_database );
		//Create regular expression patterns
		$pattern = array( '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d+)\/(\d+)\/(\d{4})/', '/\'(\d)\'/', '/\$/', '/^\'\'$/' );
		$replacement = array( '&#39;', '\'$1\'', '$3-$1-$2', '\'$1\'', '&#36;', 'NULL' );
		if ($datetime === true) $replacement = array( '&#39;', '\'$1\'', '$3-$1-$2 12:00:00', '$1', '&#36;', 'NULL' );
		
		$string = preg_replace($pattern, $replacement, $db->real_escape_string(stripslashes($string)));
		
		if (strlen($string) <= 0) $string = 'NULL';
		if ($string == '\'NOW()\'') $string = 'NOW()';
		
		return $string;
	}
		
	static function tableExists($table)
	{
		$db = end ( self::$_database );
		$table = self::sanitize($table);
		$result = $db->query("SHOW TABLES LIKE {$table}");

		if($result && $result->num_rows==1) 
	    		return true;
	    	else
	    		return false;
	}
}