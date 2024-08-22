<?php
namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Data\IData;
use BlueFission\Collections\Group;

/**
 * Class MysqlBulk
 * 
 * This class extends the Mysql class and implements the IData interface.
 * It provides bulk data storage and manipulation methods for a MySQL database.
 * 
 * @package BlueFission\Data\Storage
 */
class MySQLBulk extends MySQL implements IData
{
    /**
     * The stored data as an array of rows.
     * 
     * @var \BlueFission\Collections\Group
     */
	private $rows;

	/**
	 * The constructor method.
	 * 
	 * It calls the parent constructor and initializes the `$rows` property as a new Group object.
	 * 
	 * @param null|array $config The database configuration options.
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
		$this->rows = new Group();
		$this->rows->type('\BlueFission\Data\Storage\Mysql');
		$this->limit(0, 1000);
	}
	
	/**
	 * The `run` method.
	 * 
	 * It calls the parent `run` method and retrieves the result set into an array.
	 * Then it sets the `$rows` property to a new Group object with the result set as data.
	 * 
	 * @param string $query The SQL query to run.
	 */
	public function run( $query = "" ): IObj
	{
		parent::run($query);
		$res = [];
		if (method_exists('mysqli_result', 'fetch_all')) # Compatibility layer with PHP < 5.3
			if ($this->result)
				$res = $this->result->fetch_all( MYSQLI_ASSOC );
		else {
			if ($this->result)
				for ($res = array(); $tmp = $this->result->fetch_assoc();) $res[] = $tmp;
		}

		$this->rows = new Group( $res );
		$this->rows->type('\BlueFission\Data\Storage\Mysql');

		return $this;
	}

	/**
	 * The `result` method.
	 * 
	 * It returns the value of the `$rows` property.
	 * 
	 * @return \BlueFission\Collections\Group The stored data as an array of rows.
	 */
	public function result()
	{
		return $this->rows;
	}

	/**
	 * The `each` method.
	 * 
	 * It returns the `each` method of the `$rows` property.
	 * 
	 * @return \Generator An iterator for the stored data.
	 */
	public function each() {
		return $this->rows->each();
	}

	/**
	 * limit function
	 * 
	 * Sets the limit of the result to be retrieved.
	 * 
	 * @param int $start (optional) The start index of the result to be retrieved.
	 * @param int $end (optional) The end index of the result to be retrieved.
	 */
	public function limit($start = 0, $end = ''): IObj
	{
		$this->rowStart = $start;
		$this->rowEnd = $end;

		return $this;
	}

	/**
	 * contents function
	 * 
	 * Gets the current contents of the result.
	 * 
	 * @param mixed $data (optional) The data to be set as the contents.
	 * @return mixed The current contents of the result.
	 */
	public function contents($data = null): mixed
	{
		return $this->rows->current();
	}
}