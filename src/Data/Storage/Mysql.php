<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\DevString;
use BlueFission\Utils\DateTime;
use BlueFission\Data\IData;
use BlueFission\Connections\Database\MysqlLink;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Data\Storage\Behaviors\StorageAction;

/**
 * Mysql class is a storage implementation that provides a way to interact with a Mysql database.
 * It extends the Storage class and implements the IData interface.
 *
 * @package BlueFission\Data\Storage
 * 
 * @property-read array $_config An array that contains the configuration options for the Mysql class.
 * @property-read int $_last_row_affected The last number of rows affected by the previous database query.
 * @property-read mixed $_result The result of the previous database query.
 * @property-read array $_tables An array that contains all tables for the database.
 * @property-read array $_fields An array that contains all the fields for the tables in the database.
 * @property-read array $_relations An array that contains the relationships between tables in the database.
 * @property-read array $_conditions An array that contains conditions for database queries.
 * @property-read array $_order An array that contains the order for database queries.
 * @property-read array $_aggregate An array that contains aggregate functions for database queries.
 * @property-read array $_distinctions An array that contains distinctions options for database queries.
 * @property-read string $_query The last executed database query.
 * @property int $_row_start The starting row for the database query result.
 * @property int $_row_end The ending row for the database query result.
 * 
 */
class Mysql extends Storage implements IData
{
	protected $_config = array(
		'location'=>'',
		'name'=>'',
		'fields'=>'',
		'ignore_null'=>false,
		'auto_join'=>true,
		'save_related_tables'=>false,
		'temporary'=>false,
		'set_defaults'=>false,
		'key'=>'',
	);

	private $_last_row_affected;
	protected $_result;
		
	//declare query parts
	private $_tables = array();
	private $_fields = array();
	private $_relations = array();
	private $_conditions = array();
	private $_order = array();
	private $_aggregate = array();
	private $_distinctions = array();
	private $_query;

	protected $_row_start = 0;
	protected $_row_end = 1;
	
	/**
	 * Mysql constructor.
	 * 
	 * @param mixed $config An array or object that contains the configuration options for the Mysql class.
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	/**
	 * Activates the object by initializing the database connection and loading the object fields and related data.
	 * 
	 * @return void
	 */
	public function activate()
	{
		$this->_source = new MysqlLink();
		$this->_source->database($this->config('location'));
		$this->fields();
		
		if (!$this->_source) 
			$this->status(self::STATUS_FAILED_INIT);
	}

	/**
	 * Returns the last executed query.
	 * 
	 * @return string
	 */
	public function query()
	{
		return $this->_query;
	}

	/**
	 * Gets or sets the ID of the object.
	 * 
	 * @param mixed $id
	 * @return mixed
	 */
	public function id($id = null)
	{
		$tables = $this->tables();
		$keys = [];
		$table = $tables[0];

		foreach ($this->fields() as $field => $column) {
			$name = $column['Field'];
			if ($this->validate($name, $table)) {
				if ($column['Key'] == 'PRI' || $column['Key'] == 'UNI') {
					if (!isset($keys[$table])) $keys[$table] = $name;
					break;
				}
			}
		}
		return $this->field($keys[$table], $id);
	}

	/**
	 * Writes the data to the database.
	 *
	 * @return boolean - Returns true on success, false otherwise
	 */
	public function write()
	{
		$db = $this->_source;
		$status = self::STATUS_FAILED;
		$keys = array();
		$success = true;

		if (!$this->tables() || !$this->fields()) {
			$this->create();
		}

		$tables = $this->tables();

		if ( count($tables) < 1 ) {
			$this->status( self::STATUS_FAILED );
			return false;
		}

		$table = $tables[0];
		$table = isset( $tables[0] ) ? $tables[0] : $this->config(self::NAME_FIELD);
		
		// foreach ($this->fields() as $table=>$column)
		foreach ($this->fields() as $field=>$column)
		{
			$name = $column['Field'];
			if  ( $column['Key'] == 'PRI' || $column['Key'] == 'UNI' || $column['Key'] == 'MUL' )
			{
				if (!isset($keys[$table])) $keys[$table] = $name;
			}
			if ( is_array($this->config('fields')) && count($this->config('fields')) > 0 && !in_array($field, $this->config('fields')) ) {
				continue;
			}

			if ( !$this->validate($name, $table) )
			{
				$success = false;
			}
		}
		
		$affected_row = null;
		$tables = $this->config('save_related_tables') ? $this->tables() : [$this->tables()[0]];
		// while ( ( $table = each($tables) ) && $success )
		foreach ( $tables as $key=>$value )
		{
			if ( !$success ) {
				break;
			}

			$table = $value;
			$key = isset($keys[$table]) ? $keys[$table] : null;
			$db->config('key', $key);
			$db->config('table', $table);
			$db->config('ignore_null', $this->config('ignore_null'));

			$data = [];
			$fields = $this->_fields[$table] ?? $this->_data;

			foreach ($fields as $field) {
				$field = $field['Field'];
				if ( is_array($this->config('fields')) && count($this->config('fields')) > 0 && !in_array($field, $this->config('fields')) ) {
					continue;
				}
				$data[$field] = $this->field($field);
			}

			$success = $db->query($data);

			$this->_query = $db->stats()['query'];

			//$status = $success ? self::STATUS_FAILED : self::STATUS_SUCCESS;

			if (!$affected_row && $success && $key) {
				$affected_row = DevValue::isNotNull($this->_data[$key]) ? $this->_data[$key] : $db->last_row();
				$this->_last_row_affected = $affected_row;
			}

			$status = $success ? self::STATUS_SUCCESS : $db->status();
			$this->status( $status );
			if (!$success) {
				return false;
			} else {
				$this->id($this->lastRow());
				return true;
			}
		}
	}

	/**
	 * Returns the last affected row of the query.
	 *
	 * @return int The last affected row of the query.
	 */
	public function lastRow() {
		return $this->_last_row_affected;
	}

	/**
	 * Executes a read query to retrieve data from the database.
	 *
	 * @return array|bool The result of the query or false if the query failed.
	 */
	public function read()
	{
		$tables = $this->tables();
		if ( count($tables) < 1 ) {
			$this->status( self::STATUS_FAILED );
			return false;
		}
		$table = $tables[0];
		$fields = array();
		$data = $this->data();
		$active_fields = $this->config('fields') != '' ? DevArray::toArray( $this->config('fields') ) : array();
		$field_info = $this->fields();
		
		$relations = $this->_relations;
		$using = array();
		$join = array();
		$on = array();
		
		$distinct = array();
		$where = array('1');
		$sort = array();
		
		foreach ($data as $a=>$b) 
		{
			if ($this->whereCase($table, $a, $b))
				$where[] = $this->whereCase($table, $a, $b);
			if ($this->distinctCase($table, $a))
				$distinct[] = $this->distinctCase($table, $a);
		}
		
		// Use Ordered Sort Cases
		foreach ( $this->_order as $a=>$b )
		{
			if ( $this->exists($a) )
			{
				// $sort[] = $this->orderCase($table, $a);
				$sort_entry = $this->orderCase($table, $a);
				if ( $sort_entry ) {
					$sort[] = $sort_entry;
				}
			}
		}
		
		$left_join = '';
		$count = 1;
		
		foreach ($tables as $a) 
		{
			if ( $a != $table )
			{
				$join = $this->table($a);
				if (is_array($join)) 
				{
					if ($this->config('auto_join')) {
						$field = $this->arrayKeyIntersect($this->table($table), $join);
						foreach ($field as $b=>$c) 
						{
							if (in_array($b, $active_fields) || DevValue::isEmpty($active_fields)) $on[] = $table . ".$b  = $a.$b";
						}
					}
					if (count($relations) > 0) 
					{
						$fields = $this->arrayKeyIntersect($relations, $join);
						foreach ($fields as $b=>$c) {
							$on[] = $table . "." . $relations[$b] . "  = $a.$b";
						}
					}
	
					if ($this->config('auto_join')) {

						for ($i = $count; $i < count($tables); $i++) 
						{
							$b = $tables[$i];
							if ($a != $b) {
								$join_2 = $this->table($b);
								if (is_array($join_2)) {
									$fields = $this->arrayKeyIntersect($this->table($a), $join_2);
									foreach ($fields as $c=>$d) {
										$on[] = $a . ".$c  = $b.$c";
									}
								}
								
								$join_2 = $this->arrayKeyIntersect($this->table($b), $relations);
								if (is_array($join_2)) {	
									$fields = $this->arrayKeyIntersect($this->tables($a), $join_2);
									foreach ($fields as $c=>$d) {
										$on[] = $a . ".$c  = $b.$c";
									}
								}
							}
						}
					}
					$count++;
					
					$members = $this->arrayKeyIntersect($data, $join);
					
					foreach ($members as $b=>$c) 
					{
						if ($this->whereCase($a, $b, $c))
							$where[] = $this->whereCase($a, $b, $c);
						if ($this->orderCase($a, $b)) {
							$sort_entry = $this->orderCase($a, $b);
							if ( $sort_entry ) {
								$sort[] = $sort_entry;
							}
						}
					}
				}
			}
		}

		$left_join = '';
		if ( count ( $this->tables() ) > 1 )
			$left_join .= "INNER JOIN (" . implode(', ', array_slice($tables, 1)) . ") ON (" . implode(' AND ', $on) . ")";
		
		$select = array();
		foreach($active_fields as $a) 
		{
			if ($this->exists($a)){
				$select[] = ($this->aggregateCase($table, $a)) ? $this->aggregateCase($table, $a) : $this->fieldTable($a).'.'.$a;
			}
		}
		if (count($select) <= 0) 
		{
			$select[] = '*';
			foreach($this->_aggregate as $a=>$b) 
			{
				if ($this->exists($a))
					$select[] = ($this->aggregateCase($table, $a)) ? $this->aggregateCase($table, $a) : $table.'.'.$a;
			}
		}

		// Build query		
		$query = "SELECT " . implode(', ', $select) . " FROM `$table` $left_join WHERE " . implode(' AND ', $where); 

		if (count($distinct) > 0) $query .= " GROUP BY " . implode(', ', $distinct); 
		if (count($sort) > 0) $query .= " ORDER BY " . implode(', ', $sort); 

		$start = $this->start();
		$end = $this->end();
		$result = false;
		$query .= ((DevValue::isNotEmpty($start)) ? " LIMIT " . $this->start() . ((DevValue::isNotEmpty($end)) ? ", " . $this->end() : '') : '');
		$this->run($query);
	}

	/**
	 * Executes the query
	 * 
	 * @param string $query The query to be executed
	 * 
	 * @return void
	 */
	public function run( $query = "" )
	{
		$db = $this->_source;
		
		if ( $query == "" ) {
			$query = $this->_query;
		}

		if ($db) {
			$db->query($query);
			$this->_query = $db->stats()['query'];

			$result = $db->result();
		}
		$this->status( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );

		$this->_result = $result;

		if ($this->_result)	
		{
			$data = $this->_result->fetch_assoc();
			if ( $data )
			{
				$this->assign( $data );
				$this->_result->data_seek(0);
			}
		}
	}
	
	/**
	 * Deletes a record from the database
	 * 
	 * @return void
	 */
	public function delete()
	{
		$db = $this->_source;
		
		$tables = $this->tables();
		$table = $tables[0];
		$fields = array();
		$data = $this->data();
		$active_fields = DevArray::toArray( $this->config('fields') );
		$field_info = $this->fields();
		
		$relations = $this->_relations;
		$using = array();
		$join = array();
		$on = array();
		
		$distinct = array();
		$where = array('1');
		$sort = array();
		
		foreach ($data as $a=>$b) 
		{
			if ($this->whereCase($table, $a, $b))
				$where[] = $this->whereCase($table, $a, $b);
			if ($this->distinctCase($table, $a))
				$distinct[] = $this->distinctCase($table, $a);
		}
		
		// Use Ordered Sort Cases
		foreach ( $this->_order as $a=>$b )
		{
			if ( $this->exists($a) )
			{
				$sort = $this->orderCase($table, $a);
				$sort[] = $sort;
			}
		}	
		
		$left_join = '';
		$count = 1;
		
		foreach ($tables as $a) 
		{
			if ( $a != $table )
			{
				$join = $this->table($a);
				if (is_array($join)) 
				{
					$field = $this->arrayKeyIntersect($this->table($table), $join);
					foreach ($field as $b=>$c) 
					{ 
						if (in_array($b, $active_fields) || DevValue::isEmpty($active_fields)) $on[] = $table . ".$b  = $a.$b";
					}
	
					if (count($relations) > 0) 
					{
						$fields = $this->arrayKeyIntersect($relations, $join);
						foreach ($fields as $b=>$c) {
							$on[] = $table . "." . $relations[$b] . "  = $a.$b";
						}
					}
	
					for ($i = $count; $i < count($tables); $i++) 
					{
						$b = $tables[$i];
						if ($a != $b) {
							$join_2 = $this->table($b);
							if (is_array($join_2)) {
								$fields = $this->arrayKeyIntersect($this->table($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
							
							$join_2 = $this->arrayKeyIntersect($this->table($b), $relations);
							if (is_array($join_2)) {	
								$fields = $this->arrayKeyIntersect($this->tables($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
						}
					}
					$count++;
					
					$members = $this->arrayKeyIntersect(array_keys($data), $join);
		
					foreach ($members as $b=>$c) 
					{
						$where[] = $this->whereCase($a, $b, $c);
						$sort[] = $this->orderCase($a, $b);
					}			
				}
			}
		}

		$left_join = '';
		if ( count ( $this->tables() ) > 1 )
		$left_join = "INNER JOIN (" . implode(', ', array_slice($tables, 1)) . ") ON (" . implode(' AND ', $on) . ")";
		
		// $select = array();
		// foreach($active_fields as $a) if ($this->exists($a)) $select[] = $field_info[$a]['Table'].'.'.$a;
		// if (count($select) <= 0) $select_r[] = '*';

		// Build query		
		//$query = "SELECT " . implode(', ', $select) . " FROM `$table` $left_join WHERE " . implode(' AND ', $where); 
		$query = "DELETE FROM `$table` $left_join WHERE " . implode(' AND ', $where);

		//if (count($distinct) > 0) $query .= " GROUP BY " . implode(', ', $distinct); 
		//if (count($sort) > 0) $query .= " ORDER BY " . implode(', ', $sort); 

		//$start = $this->start();
		//$end = $this->end();

		//$query .= ((DevValue::isNotNull($start)) ? " LIMIT " . $this->start() . ((DevValue::isNotNull($end)) ? ", " . $this->end() : '') : '');

		$db->query($query);
		$this->_query =$db->stats()['query'];
		$result = $db->result();
		$this->status( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );
		
		//$this->_result = $result;
	}
	
	/**
	 * Creates a new table for the data in this object.
	 * 
	 * The table name is derived from the value of the `self::NAME_FIELD` configuration 
	 * option, or from the name of the class if `self::NAME_FIELD` is not set.
	 * The data types of each column in the table are inferred from the values in this object.
	 * 
	 * @return null
	 */
	private function create()
	{
		$db = $this->_source;
		//$tables = DevArray::toArray( $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : get_class($this) );
		$tables = DevArray::toArray( $this->config(self::NAME_FIELD) );
		$this->config(self::NAME_FIELD, $tables);

		if ( MysqlLink::tableExists( current( $this->config(self::NAME_FIELD) ) ) )
			return null;
		
		$types = array();
		$key = '';
		foreach ($this->_data as $a=>$b)
		{
			$type = '';
			if ($b)
			{
				if ( is_scalar($b))
				{
					if (is_numeric($b))
					{
						$type = is_float($b) ? "FLOAT" : "INT";
					}
					elseif (is_string($b))
					{
						if ( DateTime::stringIsDate( $b ) )
							$type = "DATETIME";
						else
						{
							$length = DevValue::isNotNull($b) ? (int)(strlen($b)*1.3) : 90; 
							$type = "VARCHAR(".$length.")";
						}
					}
				}
				else
				{
					if (DevArray::isAssoc($b) || is_object($b))
					{
						$type = "TEXT";
					}
					else
					{
						if ($this->config('set_defaults'))
						{
							$type = "SET";
							$type .= "(".implode(',', $a).")";
						}
						else 
						{
							$type = "TEXT";	
						}
					}
				}
			}
			else
			{
				if ( $a == 'date' )
				{
					$type = "DATE";
				}
				elseif ( strtolower(substr( $a, -2)) == 'id' )
				{
					$type = "INT";
				}
				else
				{
					$type = "VARCHAR(90)";
				}  
			}
			
			if ($this->config('set_defaults') && is_scalar($b))
			{
				$type .= " DEFAULT ".MysqlLink::sanitize($b);
			}
			
			if ( strtolower(substr( $a, -2)) == 'id' && $type == "INT" && $key == '')
			{
				$key = $a;
				$type .= " NOT NULL AUTO_INCREMENT, PRIMARY KEY($key)";
				$this->config('key', $key);
			}
			$types[$a] = $type;
		}
		if ($key == '' && $this->config('key'))
		{
			$key = $this->config('key');

			$type = $key . " INT NOT NULL AUTO_INCREMENT, PRIMARY KEY($key)";
		}
		
		$temp = $this->config('temporary') === true ? "TEMPORARY" : ""; 
		
		$query = "CREATE $temp TABLE IF NOT EXISTS ".$tables[0]."(";
		foreach ($types as $a=>$b)
		{
			$query .= " `$a` $b,";
		}
		$query = rtrim($query, ",");
		$query .= ")";
		$db->query($query);
		$this->_query = $db->stats()['query'];
		$result = $db->result();
		
		$this->_config[self::NAME_FIELD] = $this->tables() ? $this->tables() : $this->_config[self::NAME_FIELD];

		$status = ( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );
		$this->status($status);
	}
	
	/**
	 * Method to retrieve the data.
	 * 
	 * @param mixed $data Optional data to be returned
	 * 
	 * @return mixed The contents of the data
	 */
	public function contents( $data = null )
	{
		$data = ($this->_result) ? $this->_result : $this->data();

		return $data;
	}

	/**
	 * Method to retrieve the fields of the data source.
	 * 
	 * @return array The fields of the data source
	 */
	public function fields()
	{
		$db = $this->_source;
		// var_dump($this->config('name'));
		//if (!$this->_fields || count( $this->config(self::NAME_FIELD) ) > 0 )
		
		$tableDiff = array_merge(array_diff($this->tables(), DevArray::toArray($this->config('name'))), array_diff(DevArray::toArray($this->config('name')), $this->tables()));
		if ( count($tableDiff) > 0 ) {
			$this->_fields = [];
		}
		
		// TODO make sure this works as expected and it actually compares the arrays
		if ( !$this->_fields )
		{
			$data = array();
			//$tables = DevArray::toArray( $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : get_class($this) );
			$tables = $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : ( $this->tables() ? $this->tables() : get_class($this) );

			$tables = DevArray::toArray( $tables );
			//if ( MysqlLink::tableExists( current( $tables ) ) )
				//return array();
			
			$this->perform( State::DRAFT );
			$active_fields = DevArray::toArray( $this->config('fields') );
			foreach ($tables as $table)
			{
				$query = "SHOW COLUMNS FROM `$table`";
				$result = false;
				if ($db) {
					$db->query($query);
					$this->_query = $db->stats()['query'];
					$result = $db->result();
				}
				if ($result)
				{
					while ($column = $result->fetch_assoc()) 
					{
						$fields[$column['Field']] = $column;
						if ( in_array($column['Field'], $active_fields) || $this->is(State::DRAFT) )
							$this->_data[$column['Field']] = isset( $this->_data[$column['Field']] ) ? $this->_data[$column['Field']] : $column['Default'];
					}
					$this->_fields[$table] = $fields;
				}
				$fields = null;
			}
			
			// $this->_config[self::NAME_FIELD] = null;
			$this->halt( State::DRAFT );
			$this->perform( Event::CHANGE );
		}
		$fields = array();

		reset($this->_fields);
		// while ($table = each($this->_fields))
		// for ($i = 0; $i < count($this->_fields); $i++)
		foreach ( $this->_fields as $key=>$value )
		{
			$table = $value;
			// $table = $this->_fields[$i]['value'];
			$fields = array_merge($fields, $table);
		}
		reset($this->_fields);

		return $fields;
	}
	
	/**
	 * Returns a list of tables in `$this->_fields`.
	 * 
	 * @return array List of table names
	 */
	private function tables()
	{
		$tables = array();
		foreach ( $this->_fields as $table=>$fields)
		{
			$tables[] = $table;
		}
		return $tables;
	}
	
	/**
	 * Returns fields of the specified table.
	 * 
	 * @param string $name Table name
	 * 
	 * @return array Fields of the table
	 */
	private function table( $name )
	{
		$table = isset( $this->_fields[$name] ) ? $this->_fields[$name] : array();
		return $table;
	}
	
	/**
	 * Returns the primary key field of the first table.
	 * 
	 * @return mixed Name of the primary key field or False if not found
	 */
	public function primary() 
	{
		$output = false;
		foreach ($this->fields() as $a) {
			if($a['Key'] == 'PRI') return $a['Field'];
			if($a['Key'] == 'MUL') return $a['Field'];
			if($a['Key'] == 'UNI') return $a['Field'];
			
		}
		return $output;
	}
	
	/**
	 * Validates the specified field or all fields of the table.
	 * 
	 * @param string $field_name Name of the field to validate. If not specified, validates all fields.
	 * @param string $table Name of the table to validate. If not specified, uses the first available entry.
	 * 
	 * @return boolean Whether the validation is passed or failed
	 */
	private function validate($field_name = null, $table = null) 
	{
		$fields = $this->fields();
		// if no table is specified, used the first available entry.
		$table = $table ? $table : ( isset( $tables[0] ) ? $tables[0] :current( DevArray::toArray( $this->config(self::NAME_FIELD) ) ) );
	
		$passed = true;
		
		if (isset($fields[$field_name])) {
			$field = $fields[$field_name];
			$type = strtolower($field['Type']);
				
			//If duplicate entry
			if ($field['Key'] == 'PRI' || $field['Key'] == 'UNI') 
			{
				if (self::inDB($field_name, $this->field($field_name), $table)) 
				{
					// In what case do we really need to know this?
					$this->status("A row having field '$field_name' with value '" . $this->field($field_name) . "' already exists.");
				}
				
			} else {					
				if ( $this->field($field_name) !== 0 && $this->field($field_name) == '' ) {
					if (!$field['Null'] || $field['Null'] == 'NO') {
						if (DevString::has($type, 'date')) {
							//$this->field($field_name, dev_join_date($field_name));
							$this->field($field_name, date('Y-m-d'));
							if (!is_string($this->field($field_name)) || !DateTime::stringIsDate($this->field($field_name))) {
								$this->status("Field '$field_name' contains an inaccurate date format!");
								$passed = false;
							}
						} else {
							$this->status("Field '$field_name' cannot be empty!");
							$passed = false;
						}
					}
				} else {
					//Correct Datatype/Size
					if (DevString::has($type, 'int') || DevString::has($type, 'double') || DevString::has($type, 'float')) {
						if (!is_numeric($this->field($field_name))) {
							$this->status("Field '$field_name' must be numeric!");
							$passed = false;
						}
					}
					if (DevString::has($type, 'char') || DevString::has($type, 'text')) {
						if (!is_string($this->field($field_name)) && !is_numeric($this->field($field_name))) {
							$this->status("Field '$field_name' is not text!");
							$passed = false;
						}
						if (isset($field['LENGTH']) && DevValue::isNotNull($field['LENGTH']) && strlen($this->field($field_name)) > $field['LENGTH'])  {
							$this->status("Field '$field_name' is greater than maximum allowed string length!");
							$passed = false;
						}
					}
					if (DevString::has($type, 'date')) {
						if (!is_string($this->field($field_name)) || !DateTime::stringIsDate(($this->field($field_name)))) {
							$this->field($field_name, dev_join_date($field_name));
							if (!is_string($this->field($field_name)) || !DateTime::stringIsDate($this->field($field_name))) {
								$this->status("Field '$field_name' contains an inaccurate date format!");
								$passed = false;
							}
						}
					}
					if (DevString::has($type, 'set')) {
						if (is_array($this->field($field_name))) {
							$this->field($field_name, implode(', ', $this->field($field_name)));
						} elseif (!is_string($this->field($field_name))) {
							$this->status("Field '$field_name' contains invalid input!");
							$passed = false;
						}
					}
				}
			}
		}		
		return $passed;
	}
	
	/**
	 * Retrieve the value of row start
	 * 
	 * @return integer The value of _row_start
	 */
	private function start () 
	{
		return $this->_row_start;
	}
	
	/**
	 * Retrieve the value of row end
	 * 
	 * @return integer The value of _row_end
	 */
	private function end () 
	{
		return $this->_row_end;
	}
	
	/**
	 * Get the condition set for a member, or set a condition for a member
	 * 
	 * @param string $member The member to be set/retrieved condition
	 * @param string|array $condition The condition to be set
	 * @param mixed $value The value to be set
	 * 
	 * @return mixed Returns the condition if only $member is passed, returns boolean false on failure, returns $this if the method is successful
	 */
	public function condition($member, $condition = null, $value = null) 
	{
		//if (!$this->exists($member)) return false;
		$values = array('=', '<=>', '>', '<', '>=', '<=', '<>', 'IS', 'IS NOT', 'LIKE', 'NOT LIKE');
		if (DevValue::isNull($condition) && DevValue::isNull($value))
		{
			foreach ($this->_conditions as $a=>$b) {
				foreach (explode(',', $a) as $c) {
					if (trim($c) == $member) return $b;
				}
			}
		}
		if ( DevValue::isNotEmpty( $value ) ) 
		{
			if ( !is_array($condition) && !in_array(strtoupper($condition), $values)) return false;
			$this->_conditions[$member] = $condition;
			if (strpos($member, ',')) 
			{
				$member_r = explode(',', $member);
				foreach ($member_r as $a) $this->field($a, $value);
			} else {
				$this->field($member, $value);
			}
		}
	}
	
	/**
	 * Get the order set for a member, or set an order for a member
	 * 
	 * @param string $member The member to be set/retrieved order
	 * @param string $order The order to be set
	 * 
	 * @return mixed Returns the order if only $member is passed, returns boolean false on failure, returns $this if the method is successful
	 */
	public function order($member, $order = null) 
	{
		//if (!$this->exists($member)) return false;
		$values = array('ASC', 'DESC');
		if (DevValue::isNull($order))
		{
			foreach ($this->_order as $a=>$b) {
				foreach (explode(',', $a) as $c) {
					if (trim($c) == $member) return $b;
				}
			}
		}
		if ( !in_array(strtoupper($order), $values)) return false;
		$this->_order[$member] = $order;
	}
	
	/**
	 * Aggregates a member with a function
	 *
	 * @param string $member  The name of the member to be aggregated
	 * @param string $function  The function to aggregate the member with, e.g. 'SUM', 'AVG', 'MIN', etc.
	 *
	 * @return mixed  The aggregated result or the current object if setting the aggregation
	 */
	public function aggregate($member, $function = null) 
	{
		//if (!$this->exists($member)) return false;

		$values = array('AVG', 'BIT_AND', 'BIT_OR', 'BIT_XOR', 'COUNT', 'GROUP_CONCAT', 'MAX', 'MIN', 'STD', 'STDDEV_POP', 'STDDEV_SAMP', 'STDDEV', 'SUM', 'VAR_POP', 'VAR_SAMP', 'VARIANCE');

		if (DevValue::isNull($function))
		{
			return $this->_aggregate[$member];
		}

		if ( !in_array(strtoupper($function), $values)) return false;
		$this->_aggregate[$member] = $function;

		return $this;
	}
	
	/**
	 * Defines a relation between two members
	 *
	 * @param string $member  The name of the first member
	 * @param string $field  The name of the second member
	 *
	 * @return mixed  The current object if setting the relation or the related member if getting the relation
	 */
	public function relation($member, $field = null) 
	{
		//if (!$this->exists($member)) return false;
		
		if (DevValue::isNull($field))
			return $this->_relations[$member];
		
		$this->_relations[$field] = $member;
	}
	
	/**
	 * Adds a distinction to the list of distinctions
	 *
	 * @param string $member  The name of the member to be distinguished
	 *
	 * @return void
	 */
	public function distinction($member) 
	{
		$this->_distinctions[] = $member;
	}

	/**
	 * Finds the condition key for a given member
	 *
	 * @param string $member  The name of the member to find the condition key for
	 *
	 * @return mixed  The condition key if found, false otherwise
	 */
	private function conditionKey($member) 
	{
		if (!$this->exists($member)) return false;
		
		foreach ($this->_conditions as $a=>$b) {
			foreach (explode(',', $a) as $c) {
				if (trim($c) == $member) return $a;
			}
		}
	}
	
	/**
	 * Creates a where case statement for the query
	 *
	 * @param string $table The table to search for the member
	 * @param string $member The member to search for
	 * @param mixed $value The value to compare the member with
	 *
	 * @return string The where case statement
	 */
	private function whereCase($table, $member, $value = '') 
	{
		$tables = $this->tables();
		$table = ( DevValue::isNull( $table ) ) ? $tables[0] : $table;
		$where = '';
		$where_r = array();

		$fields = $this->table($table);
	
		$condition = $this->condition($member);
		$condition_str = is_array( $condition ) ? $condition[0] : $condition; 
		if ($condition_str === null ) {
			$condition_str = '';
		}
		if ( DevValue::isNotEmpty( $this->field($member) ) && array_key_exists($member, $fields) ) 
		{
			//Allow for fulltext searches
			if ( strtoupper( $condition_str ) == 'MATCH' ) 
			{
				$match_var = $this->conditionKey( $member );
				if ( strpos( $match_var, ',' ) ) 
				{
					$match_r = explode( ',', $match_var );
					foreach ( $match_r as $c=>$d ) 
					{
						$match_r[$c] = "$table." . trim($d);
					}
					$match_str = implode(', ', $match_r);
				} 
				elseif ( array_key_exists( $member, $this->_conditions ) ) 
				{
					$match_str = "$table.$member";
				}
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a ) 
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = "MATCH($match_str) AGAINST (" . MysqlLink::sanitize($a) . ")";
						}
					}
					$where = implode(' OR ', $where_r);
				} 
				else 
				{
					$where = "MATCH($match_str) AGAINST (" . MysqlLink::sanitize($value) . ")";
				}
			} 
			elseif ( strtoupper( $condition_str ) == 'IN' ) 
			{
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a )
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? "$condition ": "= ") . $a;
						}
						$where = implode( ' OR ', $where_r );
					}
				} 
				else 
				{
					$where = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? $condition : " = ") . "( $value )";
				}
			} 
			elseif ( strtoupper( $condition_str ) == 'NOT IN' ) 
			{
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a )
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? "$condition ": "= ") . $a;
						}
						$where = implode( ' OR ', $where_r );
					}
				} 
				else 
				{
					$where = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? $condition : " = ") . "( $value )";
				}
			} 
			else 
			{
				if ( is_array( $value ) ) 
				{
					$count = 0;
					foreach ( $value as $a ) 
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$temp_where = '';
							$condition_str = ((array_key_exists($member, $this->_conditions)) ? ((is_array($condition)) ? $condition[$count] : $condition) : " = ");
							
							if ( $condition_str == 'Like' || $condition_str == '^' ) 
							{
								$a = "$a%";
								$condition_str = "LIKE";
							}
							elseif ( $condition_str == 'likE' || $condition_str == '$' ) 
							{
								$a = "%$a";
								$condition_str = "LIKE";
							}
							elseif ( $condition_str == 'like' || $condition_str == '?' ) 
							{
								$a = "$a";
								$condition_str = "LIKE";
							}
							elseif ( strtoupper( $condition_str ) == 'LIKE' || $condition_str == "*" ) 
							{
								$a = "%$a%";
								$condition_str = "LIKE";
							}
							elseif ( strtoupper( $condition_str ) == 'NOT LIKE' || $condition_str == "!" ) 
							{
								$a = "%$a%";
								$condition_str = "NOT LIKE";
							}
							
							$temp_where = $table . ".$member " . $condition_str;
							$temp_where .= MysqlLink::sanitize( $a );	
							$where_r[] = $temp_where;
							$count++;
						}
					}
					$where = implode( ( is_array( $condition ) ) ? ' AND ' : ' OR ', $where_r );
				} 
				else 
				{
					//$where = $table . ".$member " . ( ( array_key_exists( $member, $this->_conditions ) ) ? $condition : " = " );
					$where = $table . ".$member " . ($condition ? $condition : ' = ');
					if ( $condition_str == 'Like' ) 
					{
						$value = "$value%";
					}
					elseif ( $condition_str == 'likE' ) 
					{
						$value = "%$value";
					}
					elseif ( strtoupper( $condition_str ) == 'LIKE' ) 
					{
						$value = "%$value%";
					}
					elseif ( strtoupper( $condition_str ) == 'NOT LIKE' ) 
					{
						$value = "%$value%";
					}
					$where .= MysqlLink::sanitize( $value );	
				}
			}
			if ( DevValue::isNotNull( $where ) ) $where = "($where) ";
		}

		return $where;
	}
	
	/**
	 * Order Case function
	 * 
	 * @param string $table Table name
	 * @param string $member Member of the table
	 * 
	 * @return string
	 */
	private function orderCase($table, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$sort = null;
		$members = $this->table($table);
		
		if (array_key_exists($member, $this->_order) && array_key_exists($member, $members) ) 
		{
			if (strtoupper($this->order($member)) == 'RAND()') $sort = " " . $this->order($member);
			else $sort = $table . ".$member " . $this->order($member);
		}
		
		return $sort;
	}

	/**
	 * Aggregate Case function
	 * 
	 * @param string $table Table name
	 * @param string $member Member of the table
	 * 
	 * @return string
	 */
	private function aggregateCase($table, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$agg = null;
		$members = $this->table($table);
		
		if (array_key_exists($member, $this->_aggregate) && array_key_exists($member, $members) ) 
		{
			$agg = $this->aggregate($member) . "(" . $table . ".$member " . ")";
		}
		
		return $agg;
	}
	
	/**
	 * Distinct Case function
	 * 
	 * @param string $table Table name
	 * @param string $member Member of the table
	 * 
	 * @return string
	 */
	private function distinctCase($table, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$distinct = '';
		if (in_array($member, $this->_distinctions)) 
		{
			$distinct = ' ' . $table . ".$member ";
		}
		return $distinct;
	}
	
	/**
	 * Array Key Intersect function
	 * 
	 * @param array $arr1 First array
	 * @param array $arr2 Second array
	 * 
	 * @return array
	 */
	private function arrayKeyIntersect($arr1, $arr2) 
	{
		$array = array();
		if (DevValue::isNotNull($arr2)) {
			foreach ($arr1 as $a=>$b) if (array_key_exists ( $a, $arr2)) $array[$a] = $b;
		}
		return $array;
	}
	
	/**
	 * Reset function
	 * 
	 * Resets the conditions, distinctions, aggregate, row start and end, order, and query variables
	 * 
	 * @return void
	 */
	public function reset()
	{
		$this->_conditions = array();
		$this->_distinctions = array();
		$this->_aggregate = array();
		$this->_row_start = 0;
		$this->_row_end = 1;
		$this->_order = array();
		$this->_query = null;
	}
	
	/**
	 * Check if a field exists and is active
	 *
	 * @param string $var The field to check
	 *
	 * @return bool True if the field exists and is active, false otherwise
	 */
	public function exists($var) 
	{
		$fields = $this->fields();
		$active_fields = $this->config('fields');
		if ($var != '' && array_key_exists( $var, $fields ) ) 
		{
			if (DevValue::isEmpty($active_fields) || in_array($var, $active_fields)) 
			{
				return true;
			}	
			//otherwise, proceed as normal
			else
			{
				return false;
			}
		}
		 else 
		{
			return false;
		}
		return false;
	}

	/**
	 * Get the table name for a given field
	 *
	 * @param string $field The field to get the table name for
	 *
	 * @return string The table name for the field, or an empty string if not found
	 */
	public function fieldTable( $field ) {
		foreach ( $this->_fields as $table=>$fields ) {
			if ( array_key_exists($field, $fields)) {
				return $table;
			}
		}
		return '';
	}

	/**
	 * Get the error status
	 *
	 * @return mixed The error status of the database connection or the object's status if the database connection is not set
	 */
	public function error()
	{
		$db = $this->_source;
		if ($db) {
			return $db->status();
		}
		return $this->status();
	}

	/**
	 * Check if a value is in the database
	 *
	 * @param string $field The field to check
	 * @param string $value The value to check
	 * @param string $table The table to check
	 *
	 * @return bool True if the value is in the database, false otherwise
	 */
	public static function inDB( $field, $value, $table ) {
		$db = new MysqlLink( array( 'table'=>$table ) );
		if ( DevValue::isNotNull ($value) )
		{ 
			//$db->field($field, $value);
			$db->query("select from $table where $field = $value");
			$result = $db->result();
			if ($result && count($result->num_rows > 0)) 
			{
				return true;
			}
		}
		return false;
	}
}