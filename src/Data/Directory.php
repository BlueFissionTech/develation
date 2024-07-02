<?php
namespace BlueFission\Data

use BlueFission\Collections\Hierarchical;
use BlueFission\Collections\ICollection;
use BlueFission\Data\IData;

abstract class Directory extends Hierarchical implements ICollection
{
	public function __construct( IData $storage )
	{
		parent::__construct();
		$this->_root = $storage;
	}
}