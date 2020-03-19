<?php
namespace BlueFission\Data\Storage;

use BlueFission\Data\IData;
use BlueFission\Collections\Group;

class MysqlBulk extends Mysql implements IData
{
	private $_rows;

	public function __construct( $config = null )
	{
		parent::__construct( $config );
		$this->_rows = new Group();
		$this->_rows->type('\BlueFission\Data\Storage\Mysql');
		$this->limit(0, 1000);
	}

	public function read() {
		parent::read();
		$res = array();
		if (method_exists('mysqli_result', 'fetch_all')) # Compatibility layer with PHP < 5.3
			if ($this->_result)
				$res = $this->_result->fetch_all( MYSQLI_ASSOC );
		else {
			if ($this->_result)
				for ($res = array(); $tmp = $this->_result->fetch_assoc();) $res[] = $tmp;
		}

		$this->_rows = new Group( $res );
		$this->_rows->type('\BlueFission\Data\Storage\Mysql');
	}

	public function result()
	{
		return $this->_rows;
	}

	public function each() {
		return $this->_rows->each();
	}

	public function limit($start = 0, $end = '') 
	{
		$this->_row_start = $start;
		$this->_row_end = $end;
	}

	public function contents($data = null)
	{
		return $this->_rows->current();
	}
}