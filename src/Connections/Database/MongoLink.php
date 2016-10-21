<?php
namespace BlueFission\Connections\Database;

use BlueFission\Connections\Connection;
use BlueFission\DevValue;
use BlueFission\DevArray;
use MongoDB\BSON\Javascript;
use MongoDB\Client;
use Exception;

class MongoLink extends Connection
{	

	const INSERT = 1;
	const UPDATE = 2;
	const REPLACE = 3;

	protected static $_database;

	protected $_current;

	private static $_query;
	private static $_last_row;

	private $_dataset;
	
	protected $_config = array( 'target'=>'localhost',
		'username'=>'',
		'password'=>'',
		'database'=>'',
		'collection'=>'',
		'key'=>'_id',
	);
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
		if (DevValue::isNull(self::$_database))
			self::$_database = array();
		else
			$this->_current = end ( self::$_database );

		return $this;
	}
		
	public function open()
	{
		$host = ( $this->config('target') ) ? $this->config('target') : 'localhost';
		$username = $this->config('username');
		$password = $this->config('password');
		$database = $this->config('database');
		
		$connection_id = count(self::$_database);

		if ( !class_exists('MongoDB\Client') ) return;

		try {
			$mongo = new Client("mongodb://{$username}:{$password}@{$host}:27017");
			// self::$_database[$connection_id] = $this->_connection = ($this->config('database') ? $mongo->{$this->config('database')} : $mongo);
			self::$_database[$connection_id] = $mongo;
			$this->_connection = ($this->config('database') ? $mongo->{$this->config('database')} : null);
			$this->_current = $mongo;
		} catch (Exception $e) {
			$this->status( $e->getMessage() ? $e->getMessage() : $this->error() );
		}

		$this->status( $this->error() ? $this->error() : self::STATUS_CONNECTED );
	}
		
	public function close()
	{
		$this->_connection = null;
		$this->status(self::STATUS_DISCONNECTED);
	}
	
	public function query( $query = null) {
		// 
		$db = $this->_connection;

		if ( $db )
		{
			
			if (DevValue::isNotNull($query))
			{
				$this->_query = $query;

				if (DevArray::isAssoc($query))
				{
					$this->_dataset = null;
					$this->_data = $query;
				}
				else if ( is_array($query) && !DevArray::isAssoc($query) )
				{
					$this->_dataset = $query;
					$this->_data = $query[0];
				}
				else if (is_string($query))
				{
					$this->_result = $db->command(json_decode($query));
					$this->status( $this->error() ? $this->error() : self::STATUS_SUCCESS );

					return true;
				}
			}
			$collection = $this->config('collection');
			
			$filter = null;
			$update = false;
			
			$key = self::sanitize( $this->config('key') );
			if ( $this->field($key) )
			{
				$value = self::sanitize( $this->field($key) );
				$filter = $key ? array($key => $value) : '';
				$update = true;
			}
			$data = $this->_dataset ? $this->_dataset : $this->_data;

			$type = ($update) ? ($this->config('replace') ? self::REPLACE : self::UPDATE) : self::INSERT;
			$result = false;
			try {
				$result = $this->post($collection, $data, $filter, $type);	
			} catch( Exception $e ) {
				$error = $e->getMessage();
				$this->status($error);
			}

			return $result;
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return false;
		}	
	}

	public function find($collection, $data) {
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->_connection;
		$success = false;

		if ($db)
		{				
			$document = $db->{$collection}->find($data);

			$success = ( $document ) ? true : false;

			$this->_result = $document;
		}
		else
		{
			$this->status( $status );
			return $success;
		}
		
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return $success;
	}

	private function insert($collection, &$data) 
	{
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->_connection;
		$success = false;

		if ($db)
		{						
			if ( count($this->_dataset) > 0) {
                foreach (array_chunk($data, 500) as $smallbatch) {
					$success = ( $db->{$collection}->insertMany($smallbatch) ) ? true : false;
				}
			}
			else
				$success = ( $db->{$collection}->insertOne($data) ) ? true : false;

			$this->_last_row = isset($data[$this->config('key')]) ? $data[$this->config('key')] : $this->_last_row;

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

	private function update($collection, &$data, $filter, $replace = false) 
	{
		$status = self::STATUS_NOTCONNECTED;

		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			// foreach ($data as $a) array_push($temp_values, self::sanitize($a));
			
			if ($replace){
				$success = ( $db->{$collection}->replaceMany($filter, $data) ) ? true : false;
			} else {
				$success = ( $db->{$collection}->updateMany($filter, $data) ) ? true : false;
			}

			$this->_last_row = isset($data[$this->config('key')]) ? $data[$this->config('key')] : $this->_last_row;

			$this->_result = $success;
			
			$status = ($success) ? $this->error() : self::STATUS_SUCCESS;
		}
		else
		{
			$this->status( $status );
			return $success;
		}
		
		$this->status($status);
		return $success;
	}

	private function post($collection, $data, $filter = null, $type = null) 
	{
		$db = $this->_connection;
		$status = '';
		$success = false;
		$replace = false;
		$last_row = null; 

		if ($filter == '' && ($type == self::INSERT || $type == self::UPDATE)) 
		{
			$filter = '';
		} 
		elseif (isset($filter) && $filter != '' && $type != self::REPLACE) 
		{
			$type = self::UPDATE;
		}
		if (isset($collection) && $collection != '') 
		{ //if a collection is specified
			if (count($data) >= 1) 
			{ //validates number of fields and values
				switch ($type) 
				{
				case self::INSERT:
					//attempt a database insert
					if ($this->insert($collection, $data)) 
					{
						$status = "Successfully Inserted Entry.";
						$success = true;
					} 
					else 
					{
						$status = "Insert Failed. Reason: " . $this->error();;
					}
					break;
				case self::UPDATE_SPECIFIED:
					$replace = true;
				case self::UPDATE:
					//attempt a database update
					if (isset($filter) && $filter != '') 
					{
						if ($this->update($collection, $data, $filter, $replace)) 
						{
							$status = "Successfully Updated Entry.";
							$last_row = $data[$this->config('key')];
							$success = true;
						} 
						else 
						{
							$status = "Update Failed. Reason: " . $this->error();;
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

	public function delete($collection, $data) {
		$status = self::STATUS_NOTCONNECTED;

		$db = $this->_connection;
		$success = false;

		if ($db)
		{
			$success = ( $db->{$collection}->deleteMany($data) ) ? true : false;

			$this->_result = $success;
			
			$status = ($success) ? $this->error() : self::STATUS_SUCCESS;
		}
		else
		{
			$this->status( $status );
			return $success;
		}
		
		$this->status($status);
		return $success;
	}

	public function mapReduce( $map, $reduce, $output, $action = 'replace' ) {
		$db = $this->_connection;

		// construct map and reduce functions
		$map = new Javascript($map);
		$reduce = new Javascript($reduce);
		$collection = $this->config('collection');

		$command = array(
		    "mapreduce" => $collection, 
		    "map" => $map,
		    "reduce" => $reduce,
		    "query" => $this->_data,
		    "out" => array($action => $output));
		    // "out" => array("reduce" => $output)));

		$response = $db->command($command);

		return $response;
	}

	public function connection() {
		return $this->_current;
	}

	public function result( )
	{
		return $this->_result;
	}

	public function error() {
		if ($this->_connection instanceof \MongoDB\Collection) {
	    	return $this->_connection->command(array('getlasterror' => 1));
		} else {
	    	return $this->status();
		}
	}

	public function database( $database = null )
	{
		if ( DevValue::isNull( $database ) )
			return $this->config('database');

		// $this->close();
		$this->config('database', $database);
		// $this->open();
		$this->_connection = ($this->config('database') ? $this->_current->{$this->config('database')} : null);

	}

	public function last_row() {
		return $this->_last_row;
	}
	
	public static function sanitize($string, $datetime = false) 
	{
		$string = trim($string);
		
		return $string;
	}
}