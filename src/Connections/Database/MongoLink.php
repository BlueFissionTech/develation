<?php
namespace BlueFission\Connections\Database;

use BlueFission\Connections\Connection;
use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
use MongoDB\BSON\Javascript;
use MongoDB\Client;
use Exception;

/**
 * Class MongoLink
 * 
 * Implements a connection to a MongoDB database
 */
class MongoLink extends Connection
{	

	/**
	 * Insert type constant
	 */
	const INSERT = 1;
	
	/**
	 * Update type constant
	 */
	const UPDATE = 2;
	
	/**
	 * Replace type constant
	 */
	const REPLACE = 3;

	/**
	 * Database instance variable
	 * 
	 * @var array
	 */
	protected static $database;

	/**
	 * Current instance variable
	 * 
	 * @var mixed
	 */
	protected $current;

	/**
	 * Query instance variable
	 * 
	 * @var mixed
	 */
	private static $query;
	
	/**
	 * Last row instance variable
	 * 
	 * @var mixed
	 */
	private static $lastRow;

	/**
	 * Dataset instance variable
	 * 
	 * @var mixed
	 */
	private $dataset;
	
	/**
	 * Configuration array
	 * 
	 * @var array
	 */
	protected $config = array( 'target'=>'localhost',
		'username'=>'',
		'password'=>'',
		'database'=>'',
		'collection'=>'',
		'key'=>'_id',
	);
	
	/**
	 * Constructor method
	 * 
	 * @param mixed $config Configuration array or null
	 * 
	 * @return object
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
		if (Val::isNull(self::$database))
			self::$database = array();
		else
			$this->current = end ( self::$database );

		return $this;
	}
		
	/**
	 * Opens a connection to the MongoDB database
	 * 
	 * @return void
	 */
	protected function _open(): void
	{
		$host = ( $this->config('target') ) ? $this->config('target') : 'localhost';
		$username = $this->config('username');
		$password = $this->config('password');
		$database = $this->config('database');
		
		$connection_id = count(self::$database);

		if ( !class_exists('MongoDB\Client') ) {
			throw new \Exception("MongoDB\Client not found");
		}

		try {
			$mongo = new Client("mongodb://{$username}:{$password}@{$host}:27017");
			self::$database[$connection_id] = $mongo;
			$this->connection = ($this->config('database') ? $mongo->{$this->config('database')} : null);
			$this->current = $mongo;
		} catch (Exception $e) {
			$error = ($e->getMessage() ?? $this->error()) ?? self::STATUS_FAILED;

		}

        $status = $this->connection ? self::STATUS_CONNECTED : ($error ?? self::STATUS_NOTCONNECTED);

        $this->perform( $this->connection 
			? [Event::SUCCESS, Event::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );

		$this->status( $status );
	}
		
	/**
	 * Close the connection to the MongoDB server.
	 */
	protected function _close(): void
	{
		$this->perform(State::DISCONNECTED);
	}

	/**
	 * Queries the MongoDB server and updates or inserts data.
	 *
	 * @param mixed $query The query to be executed.
	 *
	 * @return IObj
	 */
	public function query( $query = null): IObj
	{
		$this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

		$db = $this->connection;

		if ( $db )
		{
			if (Val::isNotNull($query))
			{
				$this->query = $query;

				if (Arr::isAssoc($query))
				{
					$this->dataset = null;
					$this->data = $query;
				}
				else if ( Arr::is($query) && !Arr::isAssoc($query) )
				{
					$this->dataset = $query;
					$this->data = $query[0];
				}
				else if (Str::is($query))
				{
					$this->result = $db->command(json_decode($query));
					$this->status( $this->error() ? $this->error() : self::STATUS_SUCCESS );

					return $this;
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

			$data = $this->dataset ? $this->dataset : $this->data;
			$type = ($update) ? ($this->config('replace') ? self::REPLACE : self::UPDATE) : self::INSERT;
			$result = false;

			try {
				$this->post($collection, $data, $filter, $type);	
			} catch( Exception $e ) {
				$error = $e->getMessage();
				$this->result = false;
				$this->status( $error ?? self::STATUS_FAILED );
			}
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
		}

		return $this;
	}

	/**
	 * Finds documents in a collection.
	 *
	 * @param string $collection The name of the collection.
	 * @param mixed  $data       The data to search for.
	 *
	 * @return IObj
	 */
	public function find($collection, $data): IObj
	{
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->connection;
		$success = false;

		if ( Val::isNotNull($db) ) {				
			$document = $db->{$collection}->find($data);

			$this->result = $document;
		} else {
			$this->status( $status );
			return $this;
		}
		
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return $this;
	}

	/**
	 * Inserts data into a specified collection
	 *
	 * @param string $collection The name of the collection
	 * @param array &$data The data to be inserted
	 * @return IObj
	 */
	private function insert($collection, &$data): IObj
	{
		$status = self::STATUS_NOTCONNECTED;
		
		$db = $this->connection;
		$success = false;

		if ($db)
		{						
			if ( count($this->dataset) > 0) {
				foreach (array_chunk($data, 500) as $smallbatch) {
					$success = ( $db->{$collection}->insertMany($smallbatch) ) ? true : false;
				}
			}
			else
				$success = ( $db->{$collection}->insertOne($data) ) ? true : false;

			$this->lastRow = isset($data[$this->config('key')]) ? $data[$this->config('key')] : $this->lastRow;

			$this->result = $success;
		}
		else
		{
			$this->status( self::STATUS_NOTCONNECTED );
			return $this;
		}
		
		$status = ($success) ? $db->error : self::STATUS_SUCCESS;
		$this->status($status);
		
		return $this;
	}

	/**
	 * Updates data in a specified collection
	 *
	 * @param string $collection The name of the collection
	 * @param array &$data The data to be updated
	 * @param array $filter The filter used to determine which data to update
	 * @param bool $replace Whether to replace the data or not
	 * @return IObj
	 */
	private function update($collection, &$data, $filter, $replace = false): IObj
	{
		$status = self::STATUS_NOTCONNECTED;

		$db = $this->connection;
		$success = false;

		if (Val::isNotNull($db)) {
			if ($replace){
				$success = ( $db->{$collection}->replaceMany($filter, $data) ) ? true : false;
			} else {
				$success = ( $db->{$collection}->updateMany($filter, $data) ) ? true : false;
			}

			$this->lastRow = isset($data[$this->config('key')]) ? $data[$this->config('key')] : $this->lastRow;

			$this->result = $success;
			
			$status = ($success) ? $this->error() : self::STATUS_SUCCESS;
		} else {
			$this->status( $status );
			return $this;
		}
		
		$this->status($status);
		return $this;
	}

	/**
	 * Sends a data insertion or update request to the database
	 *
	 * @param string $collection The target collection
	 * @param array $data An array of data to be inserted/updated
	 * @param string $filter A string containing the conditions to be met for the update
	 * @param int $type Specifies the type of query to be executed (INSERT, UPDATE, REPLACE)
	 * 
	 * @return IObj
	 */
	private function post($collection, $data, $filter = null, $type = null): IObj
	{
		$db = $this->connection;
		$status = '';
		$success = false;
		$replace = false;
		$last_row = null; 

		if ($filter == '' && ($type == self::INSERT || $type == self::UPDATE)) {
			$filter = '';
		} elseif (isset($filter) && $filter != '' && $type != self::REPLACE) {
			$type = self::UPDATE;
		}
		if (isset($collection) && $collection != '') { 
			//if a collection is specified
			if (count($data) >= 1) { 
				//validates number of fields and values
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
			} else {
				//if the arrays do not align or match
				$status = "Fields and Values do not match or Insufficient Fields.";
			}
		} else {
			//no table has been assigned
			$status = "No Target Table Specified";
		}
		
		$this->status($status);
		
		$this->lastRow = $last_row ? $last_row : $this->lastRow;

		return $this;
	}

	/**
	 * Deletes documents from the specified collection.
	 *
	 * @param string $collection Collection name
	 * @param array $data Arr of data to delete
	 * @return IObj
	 */
	public function delete($collection, $data): IObj
	{
		$status = self::STATUS_NOTCONNECTED;

		$db = $this->connection;
		$success = false;

		if ($db) {
			$success = ( $db->{$collection}->deleteMany($data) ) ? true : false;

			$this->result = $success;
			
			$status = ($success) ? $this->error() : self::STATUS_SUCCESS;
		} else {
			$this->status( $status );
			
			return $this;
		}
		
		$this->status($status);
		
		return $this;
	}

	/** Performs a MapReduce operation on the specified collection.
	 *
	 * @param string $map Map function
	 * @param string $reduce Reduce function
	 * @param string $output Output collection name
	 * @param string $action Action to perform on the output collection
	 * @return array Result of the operation
	 */
	public function mapReduce( $map, $reduce, $output, $action = 'replace' )
	{
		$db = $this->connection;

		// construct map and reduce functions
		$map = new Javascript($map);
		$reduce = new Javascript($reduce);
		$collection = $this->config('collection');

		$command = [
		    "mapreduce" => $collection, 
		    "map" => $map,
		    "reduce" => $reduce,
		    "query" => $this->data,
		    "out" => array($action => $output)
		];
		    // "out" => array("reduce" => $output)));

		$response = $db->command($command);

		return $response;
	}

	/**
	 * Gets the current connection.
	 *
	 * @return mixed Current connection
	 */
	public function connection()
	{
		return $this->current;
	}

	/**
	 * Gets the result of the last operation.
	 *
	 * @return mixed Result of the last operation
	 */
	public function result( )
	{
		return $this->result;
	}

	/**
	 * Gets the error of the last operation.
	 *
	 * @return mixed Error of the last operation
	 */
	public function error() {
		if ($this->connection instanceof \MongoDB\Collection) {
	    	return $this->connection->command(['getlasterror' => 1]);
		} else {
	    	return $this->status();
		}
	}

	/**
	 * Gets or sets the current database.
	 *
	 * @param string $database Database name
	 * @return string Current database name
	 */
	public function database( $database = null )
	{
		if ( Val::isNull( $database ) ) {
			return $this->config('database');
		}

		// $this->close();
		$this->config('database', $database);
		// $this->open();
		$this->connection = ($this->config('database') ? $this->current->{$this->config('database')} : null);
	}

	/**
	 * Returns the last row of data.
	 *
	 * @return mixed The last row of data.
	 */
	public function lastRow() {
		return $this->lastRow;
	}
	
	/**
	 * Sanitizes a given string.
	 *
	 * @param string $string The string to be sanitized.
	 * @param bool $datetime Whether the string is a datetime value or not.
	 *
	 * @return string The sanitized string.
	 */
	public static function sanitize($string, $datetime = false) 
	{
		$string = trim($string);
		
		return $string;
	}
}