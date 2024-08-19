<?php

namespace BlueFission\Data\Storage;

use BlueFission\Data\IData;
use BlueFission\Collections\Group;
use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Behavioral\Behaviors\Event;

/**
 * Class MongoBulk
 *
 * @package BlueFission\Data\Storage
 * 
 * This class extends the Mongo class and implements IData interface.
 * It provides an implementation for reading and writing to/from a MongoDB database in bulk.
 */
class MongoBulk extends Mongo implements IData {
	
	/**
	 * An instance of the Group class
	 * 
	 * @var Group
	 */
	private $rows;

	/**
	 * Constructor for the MongoBulk class.
	 *
	 * @param array|null $config Configuration options for the object.
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
		$this->rows = new Group();
		$this->rows->type('\BlueFission\Data\Storage\Mongo');
	}

	/**
	 * Reads data from the MongoDB database.
	 * 
	 * @return IObj
	 */
	public function read(): IObj
	{
		parent::read();
		$res = [];
		if (method_exists('mysqli_result', 'fetch_all')) # Compatibility layer with PHP < 5.3
			$res = $this->result->fetch_all( MYSQLI_ASSOC );
		else {
			if ($this->result) {
				$res = $this->result->toArray();
			}
		}

		$this->rows = new Group( $res );
		$this->rows->type('\BlueFission\Data\Storage\Mongo');

		return $this;
	}

	/**
	 * Writes data to the MongoDB database.
	 * 
	 * @return IObj
	 */
	public function write(): IObj
	{
		$db = $this->source;

		if ($db) {
			$collection = $this->config('name');

			$db->config('collection', $collection);
			// $db->config('ignore_null', $this->config('ignore_null'));
			$success = $db->query($this->rows->toArray());

			if ($success) {
				$affectedRow = $db->last_row();
				$this->lastRowAffected = $affectedRow;
			}

			$status = $success ? self::STATUS_SUCCESS : ( $db->status() ? $db->status() : self::STATUS_FAILED );
			$this->status( $status );
		}

		return $this;
	}

	/**
	 * Returns the result of the query.
	 * 
	 * @return Group An instance of the Group class.
	 */
	public function result()
	{
		return $this->rows;
	}

	/**
	 * Get the current item in the iteration
	 *
	 * @return mixed
	 */
	public function each() {
	    return $this->rows->each();
	}

	/**
	 * Limit the number of rows returned
	 *
	 * @param int $start
	 * @param int $end
	 * @return IObj
	 */
	public function limit($start = 0, $end = ''): IObj
	{
	    $this->rowStart = $start;
	    $this->rowEnd = $end;

	    return $this;
	}

	/**
	 * Get or set the data contents
	 *
	 * @param array|null $data
	 * @return mixed
	 */
	public function contents($data = null): mixed
	{
	    if ( Val::isNull($data)) {
	        return $this->rows->current() ? $this->rows->current() : parent::contents();
	    } elseif (is_array($data) && !Arr::isAssoc($data)) {
	        $this->rows = new Group( $data );
	    } elseif ( Arr::isAssoc($data) ) {
	        parent::contents($data);
	    }

	    $this->perform( Event::CHANGE ); 
	}

}